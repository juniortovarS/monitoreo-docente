<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidad de Virtualización Academica - Monitoreo de Docente</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #f8fafc;
            --surface-color: #ffffff;
            --border-color: rgba(0, 0, 0, 0.08);
            --text-primary: #0f172a;
            --text-secondary: #475569;
            
            --primary: #990000;
            --primary-hover: #730000;
            --primary-glow: rgba(153, 0, 0, 0.08);
            
            --success: #16a34a;
            --success-glow: rgba(22, 163, 74, 0.08);
            
            --warning: #ea580c;
            --warning-glow: rgba(234, 88, 12, 0.08);
            
            --danger: #dc2626;
            --danger-glow: rgba(220, 38, 38, 0.08);
            
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, rgba(153, 0, 0, 0.04) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(153, 0, 0, 0.02) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(0, 0, 0, 0.01) 0px, transparent 50%);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.5;
            overflow-x: hidden;
            padding: 2.5rem 2rem;
        }

        /* Container Layout */
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header Styles */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            animation: fadeInDown 0.6s ease-out;
        }

        .logo-section h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            color: #000000;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-section h1 i {
            color: var(--primary);
        }

        .logo-section p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            animation: fadeIn 0.8s ease-out;
        }

        .kpi-card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(12px);
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.02) 0%, transparent 100%);
            pointer-events: none;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            border-color: rgba(153, 0, 0, 0.2);
            box-shadow: 0 10px 20px -10px rgba(0, 0, 0, 0.08), 0 0 15px 0 var(--primary-glow);
        }

        .kpi-info h3 {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .kpi-info .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 0.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: baseline;
            gap: 0.25rem;
        }

        .kpi-info .kpi-subtext {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        .kpi-icon {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: #f1f5f9;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .kpi-card:hover .kpi-icon {
            transform: scale(1.1) rotate(5deg);
        }

        /* Accent Colors for Cards */
        .kpi-card.compliance .kpi-icon { color: var(--primary); background: var(--primary-glow); }
        .kpi-card.monitored .kpi-icon { color: var(--success); background: var(--success-glow); }
        .kpi-card.attendance .kpi-icon { color: var(--warning); background: var(--warning-glow); }
        .kpi-card.capacity .kpi-icon { color: var(--danger); background: var(--danger-glow); }

        /* Filter Panel */
        .filter-panel {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(12px);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            animation: fadeIn 1s ease-out;
        }

        .filter-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-title i {
            color: var(--primary);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .filter-control {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.875rem;
            outline: none;
            transition: var(--transition);
            width: 100%;
        }

        .filter-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary-glow);
        }

        .filter-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(153, 0, 0, 0.25);
        }

        .btn-secondary {
            background: var(--surface-color);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: #f1f5f9;
            color: var(--primary);
            border-color: var(--primary);
        }

        /* Report Table Container */
        .table-card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(12px);
            border-radius: 1rem;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: #f1f5f9;
            padding: 1rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.875rem;
            color: var(--text-primary);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr {
            transition: var(--transition);
        }

        tr:hover td {
            background: #f8fafc;
        }

        /* User badge / cell styles */
        .teacher-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .avatar-circle {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
            color: #ffffff;
            box-shadow: 0 0 10px rgba(153, 0, 0, 0.15);
        }

        .teacher-info {
            display: flex;
            flex-direction: column;
        }

        .teacher-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .teacher-email {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Status Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-puntual {
            background: var(--success-glow);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-tardanza {
            background: var(--warning-glow);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .badge-falto {
            background: var(--danger-glow);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Capacity Badge indicators */
        .capacity-alert {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .capacity-alert.alert-ok {
            background: rgba(16, 185, 129, 0.05);
            color: var(--success);
        }

        .capacity-alert.alert-warning {
            background: rgba(239, 68, 68, 0.08);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.15);
            font-weight: 600;
            animation: pulse-border 2s infinite;
        }

        /* Empty State */
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-secondary);
            opacity: 0.5;
            margin-bottom: 1rem;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse-border {
            0% { border-color: rgba(239, 68, 68, 0.15); }
            50% { border-color: rgba(239, 68, 68, 0.6); }
            100% { border-color: rgba(239, 68, 68, 0.15); }
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: var(--transition);
        }

        .modal.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            width: 90%;
            max-width: 600px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            transform: scale(0.95);
            transition: var(--transition);
        }

        .modal.active .modal-content {
            transform: scale(1);
        }

        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }

        .modal-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-header h3 i {
            color: var(--primary);
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.25rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .close-btn:hover {
            color: var(--primary);
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .participants-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .participant-item {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .p-info {
            display: flex;
            flex-direction: column;
        }

        .p-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .p-email {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .p-time {
            font-size: 0.75rem;
            background: rgba(153, 0, 0, 0.08);
            color: var(--primary);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <header>
        <div class="logo-section">
            <h1><i class="fa-solid fa-graduation-cap"></i> Unidad de Virtualización Academica</h1>
            <p>Monitoreo de Docente y Control de Aforo Síncrono</p>
        </div>
        <div class="header-actions">
            <span class="badge badge-puntual"><i class="fa-solid fa-circle"></i> Integración Activa</span>
        </div>
    </header>

    <!-- KPI Grid -->
    <div class="kpi-grid">
        <div class="kpi-card compliance">
            <div class="kpi-info">
                <h3>Aulas Virtuales</h3>
                <div class="kpi-value">
                    <?= isset($kpis['total_aulas']) ? $kpis['total_aulas'] : '0' ?>
                </div>
                <div class="kpi-subtext">Total aulas en Periodo</div>
            </div>
            <div class="kpi-icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
        </div>

        <div class="kpi-card monitored">
            <div class="kpi-info">
                <h3>Publicados</h3>
                <div class="kpi-value">
                    <?= isset($kpis['total_publicadas']) ? $kpis['total_publicadas'] : '0' ?>
                </div>
                <div class="kpi-subtext">Cursos activos en Canvas</div>
            </div>
            <div class="kpi-icon">
                <i class="fa-solid fa-cloud-arrow-up"></i>
            </div>
        </div>

        <div class="kpi-card attendance">
            <div class="kpi-info">
                <h3>Total Inscritos</h3>
                <div class="kpi-value">
                    <?= isset($kpis['total_inscritos']) ? $kpis['total_inscritos'] : '0' ?>
                </div>
                <div class="kpi-subtext">Matrículas reales en Canvas</div>
            </div>
            <div class="kpi-icon">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="kpi-card capacity">
            <div class="kpi-info">
                <h3>Promedio Alum</h3>
                <div class="kpi-value">
                    <?= isset($kpis['promedio_alumnos']) ? $kpis['promedio_alumnos'] : '0' ?>
                </div>
                <div class="kpi-subtext">Alumnos por aula virtual</div>
            </div>
            <div class="kpi-icon">
                <i class="fa-solid fa-chart-simple"></i>
            </div>
        </div>
    </div>

    <!-- Filter Panel -->
    <section class="filter-panel">
        <div class="filter-title">
            <i class="fa-solid fa-filter"></i> Filtros Académicos
        </div>
        <form method="GET" action="<?= site_url('SeguimientoDocente') ?>">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="periodo">Periodo</label>
                    <select name="periodo" id="periodo" class="filter-control">
                        <option value="">-- Todos --</option>
                        <option value="202601" <?= (isset($filters['periodo']) && $filters['periodo'] === '202601') ? 'selected' : '' ?>>202601</option>
                        <option value="202602" <?= (isset($filters['periodo']) && $filters['periodo'] === '202602') ? 'selected' : '' ?>>202602</option>
                        <option value="202603" <?= (isset($filters['periodo']) && $filters['periodo'] === '202603') ? 'selected' : '' ?>>202603</option>
                        <option value="202604" <?= (isset($filters['periodo']) && $filters['periodo'] === '202604') ? 'selected' : '' ?>>202604</option>
                        <option value="202605" <?= (isset($filters['periodo']) && $filters['periodo'] === '202605') ? 'selected' : '' ?>>202605</option>
                        <option value="202606" <?= (isset($filters['periodo']) && $filters['periodo'] === '202606') ? 'selected' : '' ?>>202606</option>
                        <option value="202607" <?= (isset($filters['periodo']) && $filters['periodo'] === '202607') ? 'selected' : '' ?>>202607</option>
                        <option value="202608" <?= (isset($filters['periodo']) && $filters['periodo'] === '202608') ? 'selected' : '' ?>>202608</option>
                        <option value="202609" <?= (isset($filters['periodo']) && $filters['periodo'] === '202609') ? 'selected' : '' ?>>202609</option>
                        <option value="202610" <?= (isset($filters['periodo']) && $filters['periodo'] === '202610') ? 'selected' : '' ?>>202610</option>
                        <option value="202611" <?= (isset($filters['periodo']) && $filters['periodo'] === '202611') ? 'selected' : '' ?>>202611</option>
                        <option value="202612" <?= (isset($filters['periodo']) && $filters['periodo'] === '202612') ? 'selected' : '' ?>>202612</option>
                        <option value="202502" <?= (isset($filters['periodo']) && $filters['periodo'] === '202502') ? 'selected' : '' ?>>202502</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="plataforma">Plataforma</label>
                    <select name="plataforma" id="plataforma" class="filter-control">
                        <option value="">-- Todas --</option>
                        <option value="AP" <?= (isset($filters['plataforma']) && $filters['plataforma'] === 'AP') ? 'selected' : '' ?>>Canvas AP</option>
                        <option value="USMP" <?= (isset($filters['plataforma']) && $filters['plataforma'] === 'USMP') ? 'selected' : '' ?>>Canvas USMP</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="docente_id">Docente</label>
                    <select name="docente_id" id="docente_id" class="filter-control">
                        <option value="">-- Todos los Docentes --</option>
                        <?php foreach ($docentes as $doc): ?>
                            <option value="<?= $doc['av_doc_id'] ?>" 
                                    data-zoom-license="<?= $doc['has_zoom_license'] ? '1' : '0' ?>"
                                    <?= $filters['docente_id'] == $doc['av_doc_id'] ? 'selected' : '' ?>>
                                <?= $doc['av_doc_apellidos'] . ', ' . $doc['av_doc_nombres'] ?> <?= $doc['has_zoom_license'] ? '🟢' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin-top: 0.35rem; display: flex; align-items: center; gap: 0.25rem;">
                        <input type="checkbox" id="chk_zoom_license" style="cursor: pointer; width: 14px; height: 14px;">
                        <label for="chk_zoom_license" style="font-size: 0.75rem; color: var(--text-secondary); cursor: pointer; margin-bottom: 0; user-select: none;">Solo con Licencia Zoom</label>
                    </div>
                </div>

                <div class="filter-actions" style="margin-left: auto; display: flex; gap: 0.5rem; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Buscar</button>
                    <a href="<?= site_url('SeguimientoDocente') ?>" class="btn btn-secondary"><i class="fa-solid fa-eraser"></i> Limpiar</a>
                </div>
            </div>
        </form>
    </section>

    <!-- Data Table -->
    <div class="table-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Aula</th>
                        <th>Shortname</th>
                        <th>Plataforma</th>
                        <th>Docente Asignado</th>
                        <th>Mínimo de alumnos</th>
                        <th>Máximo de alumnos</th>
                        <th>Inscritos</th>
                        <th>Estado plataforma</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reporte_data)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <p>No se encontraron aulas virtuales registradas para este periodo y plataforma.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reporte_data as $row): ?>
                            <tr>
                                <td>
                                    <a href="https://www.aulavirtualusmp.pe/sigav/mantenimiento/aula#collapse_aula_<?= $row['av_aul_id'] ?>" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='var(--primary)'">
                                        <?= $row['av_aul_descripcion'] ?>
                                    </a>
                                </td>
                                <td>
                                    <span style="font-weight: 500; color: #ffffff; font-family: monospace;"><?= $row['av_aul_codigo'] ?></span>
                                </td>
                                <td>
                                    <?php 
                                        $isAP = (strpos($row['av_aul_codigo'], 'AP') !== FALSE);
                                        $plat_label = $isAP ? 'CANVAS - AP' : 'CANVAS - USMP';
                                        $badge_class = $isAP ? 'badge-puntual' : 'badge-tardanza';
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= $plat_label ?></span>
                                </td>
                                <td>
                                    <span style="color: #ffffff; font-weight: 500;"><?= $row['docente_completo'] ?></span>
                                    <?php if ($row['av_doc_correo']): ?>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary);"><?= $row['av_doc_correo'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color: var(--text-secondary);"><?= $row['av_aul_alum_min'] !== NULL ? $row['av_aul_alum_min'] : '0' ?></span>
                                </td>
                                <td>
                                    <span style="color: var(--text-secondary);"><?= $row['av_aul_alum_max'] !== NULL ? $row['av_aul_alum_max'] : '0' ?></span>
                                </td>
                                <td class="cell-inscritos">
                                    <span class="canvas-loading" data-shortname="<?= $row['av_aul_codigo'] ?>" data-platform="<?= $isAP ? 'AP' : 'USMP' ?>" style="font-size: 0.75rem; color: var(--text-secondary);">
                                        <i class="fa-solid fa-spinner fa-spin"></i>
                                    </span>
                                </td>
                                <td class="cell-estado">
                                    <span style="font-size: 0.75rem; color: var(--text-secondary);"><i class="fa-solid fa-spinner fa-spin"></i> Cargando</span>
                                </td>
                                <td>
                                    <?php
                                        $canvas_admin_domain = $isAP ? 'https://usmp.instructure.com' : 'https://usmpvirtual.instructure.com';
                                        $administrar_url = $canvas_admin_domain . '/accounts/1?search_term=' . urlencode($row['av_aul_codigo']);
                                    ?>
                                    <a href="<?= $administrar_url ?>" target="_blank" class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.75rem; text-decoration: none; display: inline-block;">
                                        <i class="fa-solid fa-gear"></i> Administrar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Zoom Log Modal -->
<div class="modal" id="zoomLogModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-video"></i> Detalle de Asistentes en Zoom</h3>
            <button class="close-btn" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 1.25rem; font-size: 0.875rem;">
                <strong>Docente/Host:</strong> <span id="modalTeacherName">--</span><br>
                <strong>ID de Reunión:</strong> <span id="modalMeetingId" style="font-family: monospace;">--</span>
            </div>
            <div class="participants-list" id="modalParticipantsList">
                <!-- Se cargará mediante javascript simulado -->
            </div>
        </div>
    </div>
</div>

<script>
    function viewZoomLog(meetingId, teacherName) {
        document.getElementById('modalMeetingId').innerText = meetingId;
        document.getElementById('modalTeacherName').innerText = teacherName;
        
        // Simular obtención de participantes del log de Zoom
        const listContainer = document.getElementById('modalParticipantsList');
        listContainer.innerHTML = '<p style="color: var(--text-secondary); text-align: center;">Cargando participantes...</p>';
        
        // Mocking participant API response
        setTimeout(() => {
            listContainer.innerHTML = '';
            
            // Generar algunos alumnos simulados
            const mockStudents = [
                { name: "Alvaro Soto Ramos", email: "alvaro.soto@usmp.pe", duration: "115 min" },
                { name: "Beatriz Fernandez Lopez", email: "beatriz.fernandez@usmp.pe", duration: "112 min" },
                { name: "Diego Cardenas Ruiz", email: "diego.cardenas@usmp.pe", duration: "98 min" },
                { name: "Elena Gonzales Flores", email: "elena.gonzales@usmp.pe", duration: "119 min" },
                { name: "Franco Melendez Tello", email: "franco.melendez@usmp.pe", duration: "85 min" },
                { name: "Gabriela Diaz Prado", email: "gabriela.diaz@usmp.pe", duration: "110 min" }
            ];

            mockStudents.forEach(student => {
                const item = document.createElement('div');
                item.className = 'participant-item';
                item.innerHTML = `
                    <div class="p-info">
                        <span class="p-name">${student.name}</span>
                        <span class="p-email">${student.email}</span>
                    </div>
                    <span class="p-time">${student.duration}</span>
                `;
                listContainer.appendChild(item);
            });
        }, 300);

        document.getElementById('zoomLogModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('zoomLogModal').classList.remove('active');
    }

    // Cerrar modal al hacer clic afuera de él
    window.onclick = function(event) {
        const modal = document.getElementById('zoomLogModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    // Carga de aulas y estados de Canvas en tiempo real de forma asíncrona al iniciar
    document.addEventListener('DOMContentLoaded', () => {
        // Filtrado client-side de docentes por licencia de Zoom
        const chkZoom = document.getElementById('chk_zoom_license');
        const selectDocente = document.getElementById('docente_id');
        if (chkZoom && selectDocente) {
            const originalOptions = Array.from(selectDocente.options);

            function filterDocentes() {
                const onlyLicensed = chkZoom.checked;
                const currentSelectedValue = selectDocente.value;
                
                selectDocente.innerHTML = '';
                
                originalOptions.forEach(opt => {
                    const isLicensed = opt.getAttribute('data-zoom-license') === '1';
                    const isDefaultEmpty = opt.value === '';
                    
                    if (!onlyLicensed || isLicensed || isDefaultEmpty) {
                        selectDocente.appendChild(opt);
                    }
                });
                
                selectDocente.value = currentSelectedValue;
            }

            chkZoom.addEventListener('change', filterDocentes);
        }

        // Iniciar carga asíncrona de Canvas
        loadCanvasDataRealtime();
    });

    async function loadCanvasDataRealtime() {
        const loaders = document.querySelectorAll('.canvas-loading');
        if (loaders.length === 0) return;

        const shortnames = [];
        let platform = 'USMP';
        
        loaders.forEach(el => {
            shortnames.push(el.getAttribute('data-shortname'));
            platform = el.getAttribute('data-platform');
        });

        try {
            const response = await fetch(`<?= site_url('SeguimientoDocente/canvas_status_batch') ?>`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ shortnames, platform })
            });

            if (!response.ok) throw new Error("Batch failed");
            const results = await response.json();

            let totalPublicadas = 0;
            let totalInscritos = 0;

            loaders.forEach(el => {
                const shortname = el.getAttribute('data-shortname');
                const data = results[shortname] || { inscritos: 0, estado: 'No creado' };

                const tr = el.closest('tr');
                
                // Renderizar inscritos
                const cellInscritos = tr.querySelector('.cell-inscritos');
                if (cellInscritos) {
                    cellInscritos.innerHTML = `<span style="font-weight: 600; color: #ffffff;">${data.inscritos}</span>`;
                }

                // Renderizar estado
                const cellEstado = tr.querySelector('.cell-estado');
                if (cellEstado) {
                    if (data.estado === 'Publicado') {
                        cellEstado.innerHTML = `<span class="badge badge-puntual"><i class="fa-solid fa-circle-check"></i> Publicado</span>`;
                        totalPublicadas++;
                    } else if (data.estado === 'Creado') {
                        cellEstado.innerHTML = `<span class="badge badge-tardanza"><i class="fa-solid fa-circle-info"></i> Creado</span>`;
                    } else {
                        cellEstado.innerHTML = `<span class="badge badge-falto"><i class="fa-solid fa-circle-xmark"></i> ${data.estado}</span>`;
                    }
                }

                totalInscritos += parseInt(data.inscritos) || 0;
            });

            // Actualizar KPIs de una sola vez
            const cardPublicados = document.querySelector('.kpi-card.monitored .kpi-value');
            const cardInscritos = document.querySelector('.kpi-card.attendance .kpi-value');
            const cardPromedio = document.querySelector('.kpi-card.capacity .kpi-value');

            if (cardPublicados) cardPublicados.textContent = totalPublicadas;
            if (cardInscritos) cardInscritos.textContent = totalInscritos;
            if (cardPromedio && loaders.length > 0) {
                cardPromedio.textContent = Math.round(totalInscritos / loaders.length);
            }

        } catch (err) {
            console.error("Error cargando Canvas en batch:", err);
            loaders.forEach(el => {
                const tr = el.closest('tr');
                const cellInscritos = tr.querySelector('.cell-inscritos');
                if (cellInscritos) cellInscritos.innerHTML = `<span style="color: var(--text-secondary);">--</span>`;
                const cellEstado = tr.querySelector('.cell-estado');
                if (cellEstado) cellEstado.innerHTML = `<span class="badge badge-falto">Error</span>`;
            });
        }
    }
</script>

</body>
</html>
