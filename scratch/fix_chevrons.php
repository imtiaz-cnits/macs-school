<?php

function fix_chevrons($path) {
    echo "Fixing chevrons in $path\n";
    $content = file_get_contents($path);
    if ($content === false) {
        echo "Failed to read $path\n";
        return;
    }

    $target_pattern_1 = 'class="chevron-icon !gap-3"';
    $target_pattern_2 = 'class="chevron-icon"';
    
    $replacement = 'class="chevron-icon w-4 h-4 transition-transform duration-300 ease-in-out opacity-90 shrink-0 group-[.open]/section:rotate-90 group-[.open]/section:opacity-100 group-[.collapsed]/sidebar:!hidden"';
    
    $content = str_replace($target_pattern_1, $replacement, $content);
    $content = str_replace($target_pattern_2, $replacement, $content);

    if (file_put_contents($path, $content) !== false) {
        echo "Successfully updated $path\n";
    } else {
        echo "Failed to write $path\n";
    }
}

fix_chevrons('resources/views/vendor/tyro-dashboard/partials/admin-sidebar.blade.php');
fix_chevrons('resources/views/vendor/tyro-dashboard/partials/user-sidebar.blade.php');
