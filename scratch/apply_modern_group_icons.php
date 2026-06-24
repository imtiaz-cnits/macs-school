<?php

$group_icons = [
    'Student Management' => '<path stroke-linecap="round" stroke-linejoin="round" d="M22 10v6M2 10l10-5 10 5-10 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>',
    'Class Management' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
    'Exam Management' => '<rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/>',
    'Teacher Management' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'SMS Management' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    'Academic Setup' => '<path stroke-linecap="round" stroke-linejoin="round" d="m14 12-4-2-4 2M18 21h-2a2 2 0 0 1-2-2v-3a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v3a2 2 0 0 1-2 2H6M2 21h20M12 2v4M2 7l10-5 10 5v14H2Z"/>',
    'Fee Management' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
    'Administration' => '<circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
    'Resources' => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polygon points="2 17 12 22 22 17"/><polygon points="2 12 12 17 22 12"/>'
];

$top_level_icons = [
    'Dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="10" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
    'My Profile' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'
];

function update_sidebar_file($path, $group_icons, $top_level_icons) {
    echo "Updating group and top-level icons in $path...\n";
    $content = file_get_contents($path);
    if ($content === false) {
        echo "Failed to read $path\n";
        return;
    }

    // 1. Update Top-level single links
    foreach ($top_level_icons as $text => $new_paths) {
        // Find the <a> block containing <span class="sidebar-text ...">Text</span>
        // Let's use a regex to capture <a...>...<svg...>...</svg>...<span...>Text</span>...</a>
        $pattern = '/(<a\s+[^>]*class="[^"]*sidebar-link[^"]*"[^>]*>.*?)(<svg[^>]*?>)(.*?)(<\/svg>)(.*?' . preg_quote($text, '/') . '.*?<\/a>)/is';
        
        $content = preg_replace_callback($pattern, function($matches) use ($new_paths, $text) {
            $a_start = $matches[1];
            $svg_tag = $matches[2];
            $old_paths = $matches[3];
            $svg_close = $matches[4];
            $rest = $matches[5];
            
            // Adjust SVG attributes if necessary
            $svg_tag = preg_replace('/stroke-width="[^"]*"/', 'stroke-width="2"', $svg_tag);
            if (strpos($svg_tag, 'stroke-width') === false) {
                $svg_tag = str_replace('<svg', '<svg stroke-width="2"', $svg_tag);
            }
            echo "Updated top level icon for '$text'\n";
            return $a_start . $svg_tag . $new_paths . $svg_close . $rest;
        }, $content);
    }

    // 2. Update Group Section titles
    foreach ($group_icons as $title => $new_paths) {
        // Find <div class="sidebar-section-title ...">...<svg...>...</svg>...<span ...>Title</span>...</div>
        $pattern = '/(<div\s+[^>]*class="[^"]*sidebar-section-title[^"]*"[^>]*>.*?)(<svg[^>]*?>)(.*?)(<\/svg>)(.*?' . preg_quote($title, '/') . '.*?<\/div>)/is';
        
        $content = preg_replace_callback($pattern, function($matches) use ($new_paths, $title) {
            $div_start = $matches[1];
            $svg_tag = $matches[2];
            $old_paths = $matches[3];
            $svg_close = $matches[4];
            $rest = $matches[5];
            
            // Adjust SVG attributes if necessary
            $svg_tag = preg_replace('/stroke-width="[^"]*"/', 'stroke-width="2"', $svg_tag);
            if (strpos($svg_tag, 'stroke-width') === false) {
                $svg_tag = str_replace('<svg', '<svg stroke-width="2"', $svg_tag);
            }
            echo "Updated group section icon for '$title'\n";
            return $div_start . $svg_tag . $new_paths . $svg_close . $rest;
        }, $content);
    }

    if (file_put_contents($path, $content) !== false) {
        echo "Successfully wrote $path\n";
    } else {
        echo "Failed to write $path\n";
    }
}

update_sidebar_file('resources/views/vendor/tyro-dashboard/partials/admin-sidebar.blade.php', $group_icons, $top_level_icons);
update_sidebar_file('resources/views/vendor/tyro-dashboard/partials/user-sidebar.blade.php', $group_icons, $top_level_icons);
