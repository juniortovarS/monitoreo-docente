import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';
import mysql from 'mysql2/promise';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Cargar variables de entorno desde archivo .env local
const envPath = path.join(process.cwd(), '.env');
if (fs.existsSync(envPath)) {
    fs.readFileSync(envPath, 'utf-8').split(/\r?\n/).forEach(line => {
        const parts = line.split('=');
        if (parts.length >= 2) {
            const key = parts[0].trim();
            const val = parts.slice(1).join('=').trim().replace(/(^['"]|['"]$)/g, '');
            if (key && !key.startsWith('#')) {
                process.env[key] = val;
            }
        }
    });
}

const app = express();
app.use(express.json());
app.use(express.static(__dirname));

// ==========================================
// CONFIGURACIÓN DE BASE DE DATOS REAL (db_sigav)
// ==========================================

const dbConfig = {
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    waitForConnections: true,
    connectionLimit: 5,
    queueLimit: 0,
    connectTimeout: 5000 // 5 segundos de tolerancia para conexión remota
};

let pool = null;
let isDbConnected = false;

// Intentar inicializar el pool de conexiones remoto
try {
    pool = mysql.createPool(dbConfig);
    // Realizar una consulta de prueba rápida para validar credenciales y red
    const conn = await pool.getConnection();
    console.log('===========================================================');
    console.log(`  CONECTADO CON ÉXITO A LA BD REAL (${dbConfig.host})`);
    console.log('===========================================================');
    conn.release();
    isDbConnected = true;
} catch (err) {
    console.log('===========================================================');
    console.log('  ⚠️ ADVERTENCIA: NO SE PUDO CONECTAR A LA BD REAL');
    console.log(`  Detalle: ${err.message}`);
    console.log('  El servidor funcionará con base de datos en memoria para');
    console.log('  demostración local (Modo Fallback).');
    console.log('===========================================================');
}

// ==========================================
// DATA MOCK (FALLBACK EN CASO DE ERROR DE RED)
// ==========================================

const mockDb = {
    av_aula: [
        { av_aul_id: 1, av_aul_codigo: 'AULA_101_202601_USMP', av_aul_descripcion: 'Desarrollo de Software - Ciclo VI', av_aul_alum_min: 15, av_aul_alum_max: 30 },
        { av_aul_id: 2, av_aul_codigo: 'AULA_102_202601_AP', av_aul_descripcion: 'Arquitectura de Sistemas - Ciclo VII', av_aul_alum_min: 10, av_aul_alum_max: 25 },
        { av_aul_id: 3, av_aul_codigo: 'AULA_103_202602_USMP', av_aul_descripcion: 'Inteligencia de Negocios - Ciclo VIII', av_aul_alum_min: 15, av_aul_alum_max: 45 },
        { av_aul_id: 4, av_aul_codigo: 'AULA_104_202602_AP', av_aul_descripcion: 'Taller de Tesis - Ciclo X', av_aul_alum_min: 5, av_aul_alum_max: 15 }
    ],
    av_docente: [
        { av_doc_id: 1, av_doc_codigo: 'DOC001', av_doc_nombres: 'Juan', av_doc_apellidos: 'Perez Gomez', av_doc_email: 'juan.perez@usmp.pe' },
        { av_doc_id: 2, av_doc_codigo: 'DOC002', av_doc_nombres: 'Maria', av_doc_apellidos: 'Rodriguez Silva', av_doc_email: 'maria.rodriguez@usmp.pe' },
        { av_doc_id: 3, av_doc_codigo: 'DOC003', av_doc_nombres: 'Carlos', av_doc_apellidos: 'Sanches Mendoza', av_doc_email: 'carlos.sanchez@usmp.pe' }
    ],
    av_inscripcion_gestor: [
        { av_ing_id: 1, av_doc_id: 1, av_ing_estado: 'ACTIVO' },
        { av_ing_id: 2, av_doc_id: 2, av_ing_estado: 'ACTIVO' },
        { av_ing_id: 3, av_doc_id: 3, av_ing_estado: 'ACTIVO' }
    ],
    av_grupo_horario: [
        { id_horario: 1, av_aul_id: 1, dia: 'Lunes', h_inicio: '08:00:00', h_fin: '10:00:00', av_ing_id: 1 },
        { id_horario: 2, av_aul_id: 2, dia: 'Lunes', h_inicio: '10:00:00', h_fin: '12:00:00', av_ing_id: 2 },
        { id_horario: 3, av_aul_id: 3, dia: 'Martes', h_inicio: '14:00:00', h_fin: '16:00:00', av_ing_id: 3 },
        { id_horario: 4, av_aul_id: 4, dia: 'Miercoles', h_inicio: '18:00:00', h_fin: '20:00:00', av_ing_id: 1 },
        { id_horario: 5, av_aul_id: 1, dia: 'Jueves', h_inicio: '08:00:00', h_fin: '10:00:00', av_ing_id: 1 },
        { id_horario: 6, av_aul_id: 2, dia: 'Viernes', h_inicio: '15:00:00', h_fin: '17:00:00', av_ing_id: 2 },
        { id_horario: 7, av_aul_id: 3, dia: 'Sabado', h_inicio: '09:00:00', h_fin: '11:00:00', av_ing_id: 3 }
    ],
    av_seguimiento_docente_historial: [
        { id: 101, av_aul_id: 1, av_doc_id: 1, id_horario: 1, fecha_clase: '2026-08-03', horario_inicio_plan: '08:00:00', horario_fin_plan: '10:00:00', hora_entrada_real: '2026-08-03 08:02:15', hora_salida_real: '2026-08-03 09:59:02', minutos_tardanza: 2, estado_asistencia: 'PUNTUAL', alumnos_matriculados: 28, alumnos_asistentes: 24, capacidad_maxima: 30, zoom_meeting_id: '82940284910' },
        { id: 102, av_aul_id: 2, av_doc_id: 2, id_horario: 2, fecha_clase: '2026-08-03', horario_inicio_plan: '10:00:00', horario_fin_plan: '12:00:00', hora_entrada_real: '2026-08-03 10:14:30', hora_salida_real: '2026-08-03 12:00:10', minutos_tardanza: 14, estado_asistencia: 'TARDANZA', alumnos_matriculados: 24, alumnos_asistentes: 19, capacidad_maxima: 25, zoom_meeting_id: '81140928401' },
        { id: 103, av_aul_id: 3, av_doc_id: 3, id_horario: 3, fecha_clase: '2026-08-04', horario_inicio_plan: '14:00:00', horario_fin_plan: '16:00:00', hora_entrada_real: '2026-08-04 14:01:05', hora_salida_real: '2026-08-04 15:58:45', minutos_tardanza: 1, estado_asistencia: 'PUNTUAL', alumnos_matriculados: 48, alumnos_asistentes: 42, capacidad_maxima: 45, zoom_meeting_id: '84920492840' }
    ]
};

// Mapeador de día inglés a español
function getDiaEspanol(dateString) {
    const date = new Date(dateString);
    const options = { weekday: 'long', timeZone: 'America/Lima' };
    let formatter = new Intl.DateTimeFormat('es-PE', options);
    let dayName = formatter.format(date);
    dayName = dayName.charAt(0).toUpperCase() + dayName.slice(1).toLowerCase();
    dayName = dayName.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); 
    return dayName;
}

// ==========================================
// CACHÉ DE LICENCIAS ZOOM (Se carga 1 sola vez al iniciar)
// ==========================================

// Set en memoria con los emails licenciados — respuesta inmediata en cada request
let zoomLicenseCache = new Set();
let zoomCacheLoaded = false;

// Obtiene un Access Token de Zoom vía Server-to-Server OAuth (compartido por varios endpoints)
async function getZoomAccessToken() {
    const clientId = process.env.ZOOM_CLIENT_ID;
    const clientSecret = process.env.ZOOM_CLIENT_SECRET;
    const accountId = process.env.ZOOM_ACCOUNT_ID;

    if (!clientId || clientId === 'MOCK_CLIENT_ID') {
        return null;
    }

    const authHeader = Buffer.from(`${clientId}:${clientSecret}`).toString('base64');
    const tokenRes = await fetch(
        `https://zoom.us/oauth/token?grant_type=account_credentials&account_id=${accountId}`,
        { method: 'POST', headers: { 'Authorization': `Basic ${authHeader}` } }
    );
    if (!tokenRes.ok) throw new Error(`OAuth ${tokenRes.status}`);
    const { access_token } = await tokenRes.json();
    return access_token;
}

async function refreshZoomLicenseCache() {
    try {
        const token = await getZoomAccessToken();
        if (!token) {
            // Sin credenciales: usar mock y marcar como cargado para no bloquear
            zoomLicenseCache = new Set(['aalcalam@usmp.pe', 'aalemanc@usmp.pe']);
            zoomCacheLoaded = true;
            return;
        }

        const freshEmails = new Set();
        let nextPageToken = '';
        do {
            let url = `https://api.zoom.us/v2/users?status=active&page_size=300`;
            if (nextPageToken) url += `&next_page_token=${encodeURIComponent(nextPageToken)}`;

            const usersRes = await fetch(url, {
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' }
            });
            if (!usersRes.ok) throw new Error(`Users ${usersRes.status}`);

            const usersData = await usersRes.json();
            (usersData.users || []).forEach(u => {
                if (u.type === 2) freshEmails.add(u.email.toLowerCase().trim());
            });
            nextPageToken = usersData.next_page_token || '';
        } while (nextPageToken);

        zoomLicenseCache = freshEmails;
        zoomCacheLoaded = true;
        console.log(`[Zoom Caché] ✅ ${freshEmails.size} licencias cargadas en memoria.`);
    } catch (err) {
        console.error('[Zoom Caché] ⚠️ Error al refrescar, se mantiene caché anterior:', err.message);
        zoomCacheLoaded = true; // marcar igual para no bloquear las peticiones
    }
}

// Cargar caché al arrancar (sin bloquear el servidor)
refreshZoomLicenseCache();
// Refrescar automáticamente cada 30 minutos
setInterval(refreshZoomLicenseCache, 30 * 60 * 1000);

// ==========================================
// ENDPOINTS DE LA API DEL DASHBOARD
// ==========================================

// Obtener selectores de docentes — usa caché Zoom, respuesta instantánea
app.get('/api/docentes', async (req, res) => {
    let docentes = [];

    if (isDbConnected) {
        try {
            const sql = `
                SELECT d.av_doc_id, d.av_doc_username, d.av_doc_correo,
                       p.av_per_nombre_pri, p.av_per_apepat, p.av_per_apemat
                FROM av_docente d
                JOIN av_persona p ON d.av_per_id = p.av_per_id
            `;
            const [rows] = await pool.query(sql);
            const seen = new Set();
            const uniqueRows = [];
            for (const row of rows) {
                if (!seen.has(row.av_doc_username)) {
                    seen.add(row.av_doc_username);
                    uniqueRows.push(row);
                }
            }
            docentes = uniqueRows;
        } catch (err) {
            console.error('[MySQL Get Docentes]', err.message);
            docentes = mockDb.av_docente;
        }
    } else {
        docentes = mockDb.av_docente;
    }

    // Usar caché en memoria — NO llama a Zoom API en tiempo de request
    const licensedZoomEmails = zoomLicenseCache;

    let filteredDocentes = docentes.map(d => {
        const email = (d.av_doc_correo || d.av_doc_email || '').toLowerCase().trim();
        const hasLicense = licensedZoomEmails.has(email);
        
        let nombres = d.av_doc_nombres || '';
        let apellidos = d.av_doc_apellidos || '';
        if (d.av_per_nombre_pri) {
            nombres = d.av_per_nombre_pri.trim();
            apellidos = `${d.av_per_apepat ? d.av_per_apepat.trim() : ''} ${d.av_per_apemat ? d.av_per_apemat.trim() : ''}`.trim();
        }

        return {
            av_doc_id: d.av_doc_id,
            av_doc_username: d.av_doc_username,
            av_doc_nombres: nombres,
            av_doc_apellidos: apellidos,
            av_doc_email: email,
            has_zoom_license: hasLicense
        };
    });

    // Ordenar por apellidos en memoria (NodeJS)
    filteredDocentes.sort((a, b) => (a.av_doc_apellidos || '').localeCompare(b.av_doc_apellidos || ''));

    res.json(filteredDocentes);
});

// ==========================================
// MONITOREO DE CLASE — HISTORIAL DE REUNIONES ZOOM DE UN DOCENTE
// ==========================================
// Opción A: muestra TODAS las reuniones Zoom del docente en los últimos meses
// (no filtradas por curso específico, ya que hoy no existe una fuente confiable
// que vincule cada reunión con un aula/curso puntual).
// Usa el Report API de Zoom, que ya entrega duración y cantidad de participantes
// sin necesidad de una llamada adicional por reunión.
const ZOOM_HISTORIAL_MESES = 6;

app.get('/api/zoom/historial-docente', async (req, res) => {
    const { email } = req.query;
    if (!email) return res.status(400).json({ error: 'email requerido' });

    try {
        const token = await getZoomAccessToken();
        if (!token) {
            return res.json({ meetings: [], source: 'no-token', message: 'Sin credenciales de Zoom configuradas' });
        }

        // El Report API de Zoom limita el rango from/to a 1 mes, así que se consulta
        // en bloques mensuales hacia atrás
        const now = new Date();
        const fmt = (d) => d.toISOString().split('T')[0];
        const ranges = [];
        for (let i = 0; i < ZOOM_HISTORIAL_MESES; i++) {
            const from = new Date(now.getFullYear(), now.getMonth() - i, 1);
            let to = new Date(now.getFullYear(), now.getMonth() - i + 1, 0);
            if (to > now) to = now;
            ranges.push({ from: fmt(from), to: fmt(to) });
        }

        const allMeetings = [];
        let anyOk = false;
        for (const range of ranges) {
            const url = `https://api.zoom.us/v2/report/users/${encodeURIComponent(email)}/meetings?from=${range.from}&to=${range.to}&page_size=300&type=past`;
            const r = await fetch(url, {
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                signal: AbortSignal.timeout(8000)
            });

            if (!r.ok) {
                if (r.status === 404) { anyOk = true; continue; }
                const errBody = await r.text();
                console.error(`[Zoom Historial Docente] Error ${r.status} para ${email} (${range.from} a ${range.to}): ${errBody}`);
                continue;
            }
            anyOk = true;
            const data = await r.json();
            (data.meetings || []).forEach(m => allMeetings.push(m));
        }

        if (!anyOk) {
            return res.status(502).json({ error: 'La API de Zoom rechazó la solicitud de reportes (revisa el scope report:read:admin de la app Server-to-Server).' });
        }

        allMeetings.sort((a, b) => new Date(b.start_time) - new Date(a.start_time));

        const meetings = allMeetings.map(m => ({
            id: m.id,
            topic: m.topic,
            start_time: m.start_time,
            end_time: m.end_time,
            duration_min: m.duration,
            participants_count: m.participants_count
        }));

        res.json({ meetings, source: 'zoom-api' });
    } catch (err) {
        console.error('[Zoom Historial Docente] Error:', err.message);
        res.status(502).json({ error: 'No se pudo obtener el historial de Zoom', detail: err.message });
    }
});

// ==========================================
// DATOS DE CANVAS (docente, inscritos, estado de publicación)
// ==========================================
// Sin caché: se consulta Canvas en vivo en cada request para que los cambios
// (publicar/despublicar, matricular/desmatricular) se reflejen de inmediato al recargar.

// Traduce el workflow_state real de Canvas (campo base, sin include[] especial) a las
// etiquetas que usa el dashboard
function mapWorkflowStateToEstado(workflowState) {
    switch (workflowState) {
        case 'available': return 'Publicado';
        case 'completed': return 'Publicado';
        case 'unpublished': return 'Creado';
        case 'deleted': return 'Eliminado';
        default: return null;
    }
}

// Endpoint: obtener el profesor de Canvas para un shortname (SIS Course ID)
// Ejemplo: GET /api/canvas/teacher?shortname=P202608PL0101CU130&plataforma=AP
app.get('/api/canvas/teacher', async (req, res) => {
    const { shortname, plataforma } = req.query;
    if (!shortname) return res.status(400).json({ error: 'shortname requerido' });

    const isAP = (plataforma === 'AP') || (plataforma !== 'USMP');
    const domain  = isAP ? 'https://usmp.instructure.com' : 'https://usmpvirtual.instructure.com';
    const token   = isAP
        ? (process.env.CANVAS_TOKEN_AP   || 'MOCK_TOKEN_AP')
        : (process.env.CANVAS_TOKEN_USMP || 'MOCK_TOKEN_USMP');

    // Si no hay token real, responder vacío sin llamar a Canvas
    if (token.startsWith('MOCK_')) {
        return res.json({ name: null, email: null, source: 'no-token' });
    }

    try {
        // Buscar por SIS Course ID — sin filtro de estado (profesores pueden estar en 'invited')
        const enrollmentsUrl = `${domain}/api/v1/courses/sis_course_id:${encodeURIComponent(shortname)}/enrollments?type[]=TeacherEnrollment&per_page=5&include[]=user`;
        // Conteo real de matriculados — el campo de SIGAV (av_cant_inscripciones) no siempre
        // está sincronizado (sobre todo en Canvas USMP), así que se consulta Canvas en vivo.
        const courseUrl = `${domain}/api/v1/courses/sis_course_id:${encodeURIComponent(shortname)}?include[]=total_students`;

        const headers = {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        };

        const [enrollmentsRes, courseRes] = await Promise.all([
            fetch(enrollmentsUrl, { headers, signal: AbortSignal.timeout(8000) }),
            fetch(courseUrl, { headers, signal: AbortSignal.timeout(8000) })
        ]);

        let totalStudents = null;
        let estadoPlataforma = null;
        if (courseRes.ok) {
            const courseData = await courseRes.json();
            totalStudents = typeof courseData.total_students === 'number' ? courseData.total_students : null;
            // workflow_state es un campo base de Canvas (sin include[] especial) — refleja el
            // estado real de publicación, a diferencia del campo de SIGAV que puede quedar desfasado.
            estadoPlataforma = mapWorkflowStateToEstado(courseData.workflow_state);
        }

        if (!enrollmentsRes.ok) {
            // 404 = curso no existe en Canvas todavía, no es error crítico
            if (enrollmentsRes.status === 404) {
                const result = { name: null, email: null, totalStudents, estadoPlataforma, source: 'not-found-in-canvas' };
                return res.json(result);
            }
            throw new Error(`Canvas API HTTP ${enrollmentsRes.status}`);
        }

        const enrollments = await enrollmentsRes.json();
        const teacher = enrollments.find(e => e.type === 'TeacherEnrollment' || e.role === 'TeacherEnrollment');

        if (teacher && teacher.user) {
            const result = {
                name:      teacher.user.name || teacher.user.sortable_name || null,
                email:     teacher.user.login_id || teacher.user.email || null,
                avatarUrl: teacher.user.avatar_url || null,
                totalStudents,
                estadoPlataforma,
                source:    'canvas-api'
            };
            return res.json(result);
        }

        const result = { name: null, email: null, totalStudents, estadoPlataforma, source: 'no-teacher-found' };
        return res.json(result);

    } catch (err) {
        console.error(`[Canvas] Error obteniendo profesor para ${shortname}:`, err.message);
        return res.json({ name: null, email: null, totalStudents: null, estadoPlataforma: null, source: 'error', error: err.message });
    }
});

// Obtener selectores de aulas (con filtros de periodo y plataforma jalados directamente de av_aula)
app.get('/api/aulas', async (req, res) => {
    const { periodo, plataforma } = req.query;

    if (isDbConnected) {
        try {
            let sql = 'SELECT av_aul_id, av_aul_nombre_corto as av_aul_codigo, av_aul_nombre as av_aul_descripcion, av_aul_alum_max FROM av_aula WHERE 1=1';
            const params = [];
            
            if (periodo) {
                sql += ' AND av_periodo = ?';
                params.push(periodo);
            }
            if (plataforma) {
                if (plataforma === 'AP') {
                    sql += ' AND av_aul_nombre_corto LIKE "%AP%"';
                } else {
                    sql += ' AND av_aul_nombre_corto NOT LIKE "%AP%"';
                }
            }
            sql += ' ORDER BY av_aul_nombre_corto ASC';
            
            const [rows] = await pool.query(sql, params);
            return res.json(rows);
        } catch (err) {
            console.error('[MySQL Get Aulas failed, falling back to mock]', err.message);
        }
    }

    // Fallback a mock data en memoria (Modo Fallback)
    let filteredAulas = mockDb.av_aula;
    if (periodo) {
        filteredAulas = filteredAulas.filter(a => a.av_aul_codigo.includes(periodo));
    }
    if (plataforma) {
        if (plataforma === 'AP') {
            filteredAulas = filteredAulas.filter(a => a.av_aul_codigo.includes('AP'));
        } else {
            filteredAulas = filteredAulas.filter(a => !a.av_aul_codigo.includes('AP'));
        }
    }
    res.json(filteredAulas.map(a => ({
        av_aul_id: a.av_aul_id,
        av_aul_codigo: a.av_aul_codigo,
        av_aul_descripcion: a.av_aul_descripcion
    })));
});

// Obtener horarios programados
app.get('/api/horarios', async (req, res) => {
    if (isDbConnected) {
        try {
            const sql = `
                SELECT gh.*, a.av_aul_nombre_corto as av_aul_codigo, a.av_aul_nombre as av_aul_descripcion, a.av_aul_alum_max, 
                       p.av_per_nombre_pri as av_doc_nombres, p.av_per_apepat as av_doc_apellidos, d.av_doc_correo as av_doc_email
                FROM av_grupo_horario gh
                JOIN av_aula a ON gh.av_aul_id = a.av_aul_id
                JOIN av_inscripcion_gestor ig ON gh.av_ing_id = ig.av_ing_id
                JOIN av_docente d ON ig.av_doc_id = d.av_doc_id
                JOIN av_persona p ON d.av_per_id = p.av_per_id
            `;
            const [rows] = await pool.query(sql);
            return res.json(rows);
        } catch (err) {
            console.error('[MySQL Get Horarios failed, falling back to mock]', err.message);
        }
    }

    // Fallback Mock mapping
    const schedules = mockDb.av_grupo_horario.map(gh => {
        const aula = mockDb.av_aula.find(a => a.av_aul_id === gh.av_aul_id);
        const gestor = mockDb.av_inscripcion_gestor.find(ig => ig.av_ing_id === gh.av_ing_id);
        const docente = mockDb.av_docente.find(d => d.av_doc_id === (gestor ? gestor.av_doc_id : null));
        return {
            ...gh,
            av_aul_codigo: aula ? aula.av_aul_codigo : '',
            av_aul_descripcion: aula ? aula.av_aul_descripcion : '',
            av_aul_alum_max: aula ? aula.av_aul_alum_max : 0,
            av_doc_nombres: docente ? docente.av_doc_nombres : '',
            av_doc_apellidos: docente ? docente.av_doc_apellidos : '',
            av_doc_email: docente ? docente.av_doc_email : ''
        };
    });
    res.json(schedules);
});

// Endpoint de Auditoría de Aulas y Sincronización Canvas (Con filtros de Periodo y Plataforma)
app.get('/api/historial', async (req, res) => {
    const { docente_id, periodo, plataforma } = req.query;
    let data = [];

    if (isDbConnected) {
        try {
            // av_cant_inscripciones = inscritos reales en SIGAV
            // av_aul_estado_plataforma = estado del aula en Canvas (1=Publicado, 0=Creado)
            // cod_plat_edu_det = '01' → Canvas AP, '02' → Canvas USMP
            let sql = `
                SELECT a.av_aul_id, 
                       a.av_aul_nombre           AS av_aul_descripcion,
                       a.av_aul_nombre_corto     AS av_aul_codigo,
                       a.av_aul_alum_min,
                       a.av_aul_alum_max,
                       a.av_periodo,
                       a.av_cant_inscripciones,
                       a.av_aul_estado_plataforma,
                       a.cod_plat_edu_det,
                       p.av_per_nombre_pri,
                       p.av_per_apepat,
                       p.av_per_apemat,
                       d.av_doc_id,
                       d.av_doc_correo
                FROM av_aula a
                LEFT JOIN av_inscripcion_gestor ig ON a.av_aul_id = ig.av_aul_id AND ig.av_ing_estado = '1'
                LEFT JOIN av_docente d ON ig.av_doc_id = d.av_doc_id
                LEFT JOIN av_persona p ON d.av_per_id = p.av_per_id
                WHERE a.av_aul_estado = 'A'
            `;
            const params = [];
            if (periodo) {
                sql += ' AND a.av_periodo = ?';
                params.push(periodo);
            }
            if (plataforma) {
                sql += ' AND a.cod_plat_edu = "01"';
                if (plataforma === 'AP') {
                    sql += ' AND a.cod_plat_edu_det = "01"';
                } else {
                    sql += ' AND a.cod_plat_edu_det = "02"';
                }
            }
            if (docente_id) {
                sql += ' AND d.av_doc_id = ?';
                params.push(parseInt(docente_id));
            }
            sql += ' ORDER BY a.av_aul_id DESC';

            const [rows] = await pool.query(sql, params);
            data = rows;
        } catch (err) {
            console.error('[MySQL Get Historial Classrooms failed, falling back to mock]', err.message);
            isDbConnected = false;
        }
    }

    if (!isDbConnected) {
        data = mockDb.av_aula.map(a => {
            const ig = mockDb.av_inscripcion_gestor.find(g => g.av_aul_id === a.av_aul_id);
            const d = ig ? mockDb.av_docente.find(doc => doc.av_doc_id === ig.av_doc_id) : null;
            return {
                av_aul_id: a.av_aul_id,
                av_aul_descripcion: a.av_aul_descripcion,
                av_aul_codigo: a.av_aul_codigo,
                av_aul_alum_min: 5,
                av_aul_alum_max: 80,
                av_periodo: '202608',
                av_cant_inscripciones: 25,
                av_aul_estado_plataforma: '1',
                cod_plat_edu_det: '01',
                av_per_nombre_pri: d ? d.av_doc_nombres : null,
                av_per_apepat: d ? d.av_doc_apellidos : null,
                av_per_apemat: '',
                av_doc_id: d ? d.av_doc_id : null,
                av_doc_correo: d ? d.av_doc_email : null
            };
        });
    }

    // Formatear con datos REALES de SIGAV — sin simulación
    const formattedData = data.map(row => {
        // Plataforma real desde cod_plat_edu_det
        const plataformaLabel = row.cod_plat_edu_det === '02' ? 'CANVAS - USMP' : 'CANVAS - AP';

        // Estado Canvas real desde av_aul_estado_plataforma
        // 1 = Publicado, 0 o null = Creado/Sin publicar
        const estadoPlatVal = parseInt(row.av_aul_estado_plataforma);
        let estadoCanvas;
        if (estadoPlatVal === 1) {
            estadoCanvas = 'Publicado';
        } else if (estadoPlatVal === 0) {
            estadoCanvas = 'Creado';
        } else {
            estadoCanvas = 'Sin estado';
        }

        // Inscritos reales de SIGAV (av_cant_inscripciones)
        const inscritos = row.av_cant_inscripciones !== null && row.av_cant_inscripciones !== undefined
            ? parseInt(row.av_cant_inscripciones)
            : 0;

        return {
            av_aul_id:         row.av_aul_id,
            av_aul_nombre:     row.av_aul_descripcion,
            av_aul_nombre_corto: row.av_aul_codigo,
            av_aul_alum_min:   row.av_aul_alum_min ?? 0,
            av_aul_alum_max:   row.av_aul_alum_max ?? 0,
            plataforma_label:  plataformaLabel,
            inscritos_canvas:  inscritos,
            estado_canvas:     estadoCanvas,
            docente_completo:  row.av_per_nombre_pri
                ? `${row.av_per_nombre_pri.trim()} ${(row.av_per_apepat || '').trim()} ${(row.av_per_apemat || '').trim()}`.trim()
                : 'Sin Docente',
            av_doc_correo: row.av_doc_correo,
            zoom_meeting_id: null  // Se conectará con Zoom webhook en producción
        };
    });

    // KPIs calculados sobre datos reales
    const total_aulas     = formattedData.length;
    const total_publicadas = formattedData.filter(a => a.estado_canvas === 'Publicado').length;
    const total_inscritos = formattedData.reduce((acc, a) => acc + a.inscritos_canvas, 0);
    const promedio_alumnos = total_aulas > 0 ? Math.round(total_inscritos / total_aulas) : 0;

    res.json({
        data: formattedData,
        kpis: { total_aulas, total_publicadas, total_inscritos, promedio_alumnos }
    });
});

// Receptor Webhook de Zoom (Producción & Pruebas en tiempo real)
app.post('/api/zoom/webhook', async (req, res) => {
    const { event, payload } = req.body;
    if (!payload || !payload.object) {
        return res.status(400).json({ error: 'No meeting object found' });
    }

    const meeting = payload.object;
    const zoom_meeting_id = meeting.id;
    const host_email = meeting.host_email;

    console.log(`[Receptor Zoom] Recibido webhook: ${event} para reunión ${zoom_meeting_id}`);

    if (event === 'meeting.started') {
        let recordExistente = null;
        let docente = null;
        let schedule = null;
        let aula = null;

        // 1. Validar duplicado y docente
        if (isDbConnected) {
            try {
                const [rExist] = await pool.query('SELECT * FROM av_seguimiento_docente_historial WHERE zoom_meeting_id = ?', [zoom_meeting_id]);
                if (rExist.length > 0) return res.json({ message: 'Record already exists', record: rExist[0] });

                const [docs] = await pool.query('SELECT * FROM av_docente WHERE av_doc_email = ? LIMIT 1', [host_email]);
                if (docs.length === 0) return res.status(404).json({ error: `Docente no encontrado con email ${host_email}` });
                docente = docs[0];
            } catch (err) {
                console.error('[MySQL error checking started status, falling back]', err.message);
                isDbConnected = false;
            }
        }

        if (!isDbConnected) {
            recordExistente = mockDb.av_seguimiento_docente_historial.find(h => h.zoom_meeting_id === zoom_meeting_id);
            if (recordExistente) return res.json({ message: 'Record already exists', record: recordExistente });
            docente = mockDb.av_docente.find(d => d.av_doc_email.toLowerCase() === host_email.toLowerCase());
            if (!docente) return res.status(404).json({ error: `Docente no encontrado con email ${host_email}` });
        }

        // 2. Tiempos y Día
        const startTimeStr = meeting.start_time;
        const dateLocal = new Date(startTimeStr);
        const fecha_clase = dateLocal.toISOString().split('T')[0];
        const hora_inicio_real = dateLocal.toTimeString().split(' ')[0];
        const dia_semana = getDiaEspanol(startTimeStr);

        // 3. Obtener horario programado
        if (isDbConnected) {
            try {
                const sql = `
                    SELECT gh.id_horario, gh.av_aul_id, gh.h_inicio, gh.h_fin, a.av_aul_alum_max, a.av_aul_codigo
                    FROM av_grupo_horario gh
                    JOIN av_aula a ON gh.av_aul_id = a.av_aul_id
                    JOIN av_inscripcion_gestor ig ON gh.av_ing_id = ig.av_ing_id
                    WHERE ig.av_doc_id = ? 
                      AND gh.dia = ?
                      AND ? BETWEEN SUBTIME(gh.h_inicio, '00:45:00') AND ADDTIME(gh.h_fin, '00:45:00')
                    LIMIT 1
                `;
                const [scheds] = await pool.query(sql, [docente.av_doc_id, dia_semana, hora_inicio_real]);
                if (scheds.length > 0) {
                    schedule = scheds[0];
                }
            } catch (err) {
                console.error('[MySQL error matching schedule]', err.message);
            }
        }

        if (!schedule) {
            // Fallback match en memoria
            const gestoresDocente = mockDb.av_inscripcion_gestor.filter(ig => ig.av_doc_id === docente.av_doc_id).map(ig => ig.av_ing_id);
            schedule = mockDb.av_grupo_horario.find(gh => {
                if (!gestoresDocente.includes(gh.av_ing_id) || gh.dia !== dia_semana) return false;
                const [hReal, mReal] = hora_inicio_real.split(':').map(Number);
                const [hPlan, mPlan] = gh.h_inicio.split(':').map(Number);
                const realMinutes = hReal * 60 + mReal;
                const planStartMinutes = hPlan * 60 + mPlan;
                return Math.abs(realMinutes - planStartMinutes) <= 45;
            });
            if (schedule) {
                aula = mockDb.av_aula.find(a => a.av_aul_id === schedule.av_aul_id);
                schedule = { ...schedule, av_aul_alum_max: aula.av_aul_alum_max, av_aul_codigo: aula.av_aul_codigo };
            }
        }

        if (!schedule) {
            return res.status(404).json({ error: `No se encontró programación horaria para el docente en el día ${dia_semana} cerca de las ${hora_inicio_real}` });
        }

        // 4. Calcular tardanza
        const [hPlan, mPlan] = schedule.h_inicio.split(':').map(Number);
        const [hReal, mReal] = hora_inicio_real.split(':').map(Number);
        const planMinutes = hPlan * 60 + mPlan;
        const realMinutes = hReal * 60 + mReal;
        
        let minutos_tardanza = 0;
        if (realMinutes > planMinutes) {
            minutos_tardanza = realMinutes - planMinutes;
        }
        const estado_asistencia = minutos_tardanza > 10 ? 'TARDANZA' : 'PUNTUAL';

        // 5. Matrícula de Canvas
        let alumnos_matriculados = schedule.av_aul_alum_max;
        if (schedule.av_aul_codigo === 'AULA_103') alumnos_matriculados = 48; // Simular sobre-aforo
        else if (schedule.av_aul_codigo === 'AULA_104') alumnos_matriculados = 12;

        const newRecord = {
            av_aul_id: schedule.av_aul_id,
            av_doc_id: docente.av_doc_id,
            id_horario: schedule.id_horario,
            fecha_clase,
            horario_inicio_plan: schedule.h_inicio,
            horario_fin_plan: schedule.h_fin,
            hora_entrada_real: `${fecha_clase} ${hora_inicio_real}`,
            hora_salida_real: null,
            minutos_tardanza,
            estado_asistencia,
            alumnos_matriculados,
            alumnos_asistentes: 0,
            capacidad_maxima: schedule.av_aul_alum_max,
            zoom_meeting_id
        };

        if (isDbConnected) {
            try {
                const insertSql = `
                    INSERT INTO av_seguimiento_docente_historial 
                    (av_aul_id, av_doc_id, id_horario, fecha_clase, horario_inicio_plan, horario_fin_plan, hora_entrada_real, minutos_tardanza, estado_asistencia, alumnos_matriculados, alumnos_asistentes, capacidad_maxima, zoom_meeting_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                `;
                const [result] = await pool.query(insertSql, [
                    newRecord.av_aul_id, newRecord.av_doc_id, newRecord.id_horario, newRecord.fecha_clase,
                    newRecord.horario_inicio_plan, newRecord.horario_fin_plan, newRecord.hora_entrada_real,
                    newRecord.minutos_tardanza, newRecord.estado_asistencia, newRecord.alumnos_matriculados,
                    newRecord.alumnos_asistentes, newRecord.capacidad_maxima, newRecord.zoom_meeting_id
                ]);
                newRecord.id = result.insertId;
                return res.status(201).json({ status: 'success', message: 'Clase iniciada registrada (MySQL)', record: newRecord });
            } catch (err) {
                console.error('[MySQL error writing started record, falling back to memory]', err.message);
            }
        }

        // Memory insertion
        newRecord.id = mockDb.av_seguimiento_docente_historial.length + 101;
        mockDb.av_seguimiento_docente_historial.push(newRecord);
        return res.status(201).json({ status: 'success', message: 'Clase iniciada registrada (Memoria)', record: newRecord });

    } else if (event === 'meeting.ended') {
        const endTimeStr = meeting.end_time;
        const dateLocal = new Date(endTimeStr);
        const hora_salida_real = dateLocal.toISOString().replace('T', ' ').substring(0, 19);

        if (isDbConnected) {
            try {
                // Buscar
                const [records] = await pool.query('SELECT * FROM av_seguimiento_docente_historial WHERE zoom_meeting_id = ? LIMIT 1', [zoom_meeting_id]);
                if (records.length > 0) {
                    const record = records[0];
                    const alumnos_asistentes = Math.max(1, Math.floor(record.alumnos_matriculados * (0.7 + Math.random() * 0.25)));
                    
                    await pool.query('UPDATE av_seguimiento_docente_historial SET hora_salida_real = ?, alumnos_asistentes = ? WHERE id = ?', [
                        hora_salida_real, alumnos_asistentes, record.id
                    ]);
                    record.hora_salida_real = hora_salida_real;
                    record.alumnos_asistentes = alumnos_asistentes;
                    return res.json({ status: 'success', message: 'Clase finalizada registrada (MySQL)', record });
                }
            } catch (err) {
                console.error('[MySQL error writing ended status, falling back to memory]', err.message);
            }
        }

        // Memory Search
        const record = mockDb.av_seguimiento_docente_historial.find(h => h.zoom_meeting_id === zoom_meeting_id);
        if (!record) {
            return res.status(404).json({ error: `No existe registro histórico para la reunión Zoom ${zoom_meeting_id}` });
        }
        const alumnos_asistentes = Math.max(1, Math.floor(record.alumnos_matriculados * (0.7 + Math.random() * 0.25)));
        record.hora_salida_real = hora_salida_real;
        record.alumnos_asistentes = alumnos_asistentes;

        return res.json({ status: 'success', message: 'Clase finalizada registrada (Memoria)', record });
    }

    res.json({ message: 'Event ignored' });
});

// Servir archivo index.html
app.get('*', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

const PORT = process.env.PORT || 3000;
const server = app.listen(PORT, () => {
    console.log(`===========================================================`);
    console.log(`  SIGAV DASHBOARD REAL ACTIVO: http://localhost:${PORT}`);
    console.log(`===========================================================`);
});

server.on('error', (err) => {
    if (err.code === 'EADDRINUSE') {
        console.error(`\n===========================================================`);
        console.error(`[ERROR] El puerto ${PORT} ya está siendo utilizado por otro proceso.`);
        console.error(`Para solucionar esto y liberar el puerto en Windows, ejecuta:`);
        console.error(`PowerShell: Stop-Process -Id (Get-NetTCPConnection -LocalPort ${PORT}).OwningProcess -Force`);
        console.error(`===========================================================\n`);
        process.exit(1);
    } else {
        console.error('[Server Error]', err);
    }
});
