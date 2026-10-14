<?php $menu = menuPorRol($usuario['rol']); $rutaActual = $_GET['route'] ?? ''; ?>
<aside class="sidebar-vet p-3">
    <ul class="nav nav-pills flex-column gap-1">
        <?php foreach ($menu as $item): ?>
        <li class="nav-item">
            <a class="nav-link <?= str_starts_with($rutaActual, $item['route']) ? 'active' : 'text-dark' ?>"
               href="index.php?route=<?= e($item['route']) ?>">
                <i class="bi <?= e($item['icon']) ?>"></i> <?= e($item['label']) ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</aside>
