<?php

function fix_sidebar_titles($path) {
    echo "Fixing section titles in $path\n";
    $content = file_get_contents($path);
    if ($content === false) {
        echo "Failed to read $path\n";
        return;
    }

    $target = 'class="sidebar-section-title !gap-3"';
    $replacement = 'class="sidebar-section-title cursor-pointer flex items-center justify-between select-none py-2.5 !px-4 !mx-1.5 !mb-[2px] !rounded-lg !text-[12px] !font-black !uppercase !tracking-[0.5px] !text-[var(--sidebar-foreground)] transition-all duration-150 ease-in-out whitespace-nowrap overflow-hidden !no-underline !shadow-none hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] group-[.open]/section:!bg-[var(--sidebar-accent)] group-[.open]/section:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:!justify-center group-[.collapsed]/sidebar:!py-2.5 group-[.collapsed]/sidebar:!px-0 group-[.collapsed]/sidebar:!mx-2.5 group-[.collapsed]/sidebar:!rounded-lg !gap-3"';

    $content = str_replace($target, $replacement, $content);

    // Just in case there's any other target format:
    $target2 = 'class="sidebar-section-title"';
    $content = str_replace($target2, $replacement, $content);

    if (file_put_contents($path, $content) !== false) {
        echo "Successfully updated $path\n";
    } else {
        echo "Failed to write $path\n";
    }
}

fix_sidebar_titles('resources/views/vendor/tyro-dashboard/partials/admin-sidebar.blade.php');
fix_sidebar_titles('resources/views/vendor/tyro-dashboard/partials/user-sidebar.blade.php');
