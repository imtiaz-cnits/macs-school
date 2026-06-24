<?php

$icon_paths = [
    // Student Management Submenu
    'Students Lists' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'Add New Students' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/>',
    'ID Card Generation' => '<path d="M17 18a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2"/><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="12" cy="10" r="3"/>',
    'Student Promotion' => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',

    // Class Management Submenu
    'Class List' => '<rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>',
    'Class Routine' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
    'Attendance' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>',
    'Attendance Report' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',

    // Exam Management Submenu
    'Exam List' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
    'Exam Routine' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
    'Exam Setup' => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
    'Admit Card' => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 11v2"/><path d="M13 17v2"/>',
    'Seat Plan' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
    'Marks Entry' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
    'Results' => '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
    'Tabulation Sheet' => '<path d="M12 3v18"/><path d="M3 12h18"/><rect x="3" y="3" width="18" height="18" rx="2"/>',
    'Certificates' => '<path d="M4 22V4a2 2 0 0 1 2-2h8.5L20 7.5V20a2 2 0 0 1-2 2H4Z"/><path d="M14 2v6h6"/><circle cx="10" cy="13" r="3"/><path d="m12 16 1.5 5-3.5-2-3.5 2 1.5-5"/>',

    // Teacher Management Submenu
    'Teachers List' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'Add Teacher' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/>',

    // SMS Management Submenu
    'Notice SMS' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
    'Result SMS' => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
    'SMS Report' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',

    // Academic Setup Submenu
    'Sections' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/>',
    'Shifts' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    'Sessions' => '<path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/>',
    'Branches' => '<path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v3.248M6 15v-9a2 2 0 0 1 2-2h2"/><circle cx="18" cy="15" r="3"/><circle cx="6" cy="18" r="3"/><circle cx="14" cy="15" r="3"/>',
    'Subjects' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
    'Grades' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',

    // Fee Management Submenu
    'Fee Categories' => '<path d="M2.247 13.062 12 3.31a2 2 0 0 1 2.829 0l6.86 6.86a2 2 0 0 1 0 2.829l-9.753 9.753a2 2 0 0 1-2.829 0L2.247 15.89a2 2 0 0 1 0-2.828Z"/><path d="M5 8.5h.01"/>',
    'Fee Setup' => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
    'Generate Invoices' => '<path d="M4 22V4a2 2 0 0 1 2-2h8.5L20 7.5V20a2 2 0 0 1-2 2H4Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15h6"/>',
    'Collect Fees' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 10h18a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2z"/><circle cx="13" cy="16" r="2"/>',
    'Financial Reports' => '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>',
    'Category Summary' => '<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="16" y1="14" x2="16" y2="18"/><path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/><path d="M12 14h.01"/><path d="M8 14h.01"/><path d="M12 18h.01"/><path d="M8 18h.01"/>',

    // Administration Submenu
    'Users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'Roles' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    'Privileges' => '<path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
    'Settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>'
];

function update_submenu_icon_in_content($content, $text, $new_path_content) {
    $pattern_span = '/<span class="sidebar-text[^>]*>\s*' . preg_quote($text, '/') . '\s*<\/span>/i';
    if (!preg_match($pattern_span, $content, $matches, PREG_OFFSET_CAPTURE)) {
        return [false, $content];
    }
    
    $span_pos = $matches[0][1];
    $a_start_pos = strrpos(substr($content, 0, $span_pos), '<a');
    if ($a_start_pos === false) return [false, $content];

    $a_end_pos = strpos($content, '</a>', $span_pos);
    if ($a_end_pos === false) return [false, $content];
    $a_end_pos += 4;

    $a_block = substr($content, $a_start_pos, $a_end_pos - $a_start_pos);
    
    $svg_pattern = '/<svg([^>]*?)>.*?<\/svg>/is';
    
    $new_a_block = preg_replace_callback($svg_pattern, function($svg_matches) use ($new_path_content) {
        $svg_tag_attributes = $svg_matches[1];
        // Change stroke-width to 2
        $svg_tag_attributes = preg_replace('/stroke-width="[^"]*"/', 'stroke-width="2"', $svg_tag_attributes);
        if (strpos($svg_tag_attributes, 'stroke-width') === false) {
            $svg_tag_attributes .= ' stroke-width="2"';
        }
        return '<svg' . $svg_tag_attributes . '>' . $new_path_content . '</svg>';
    }, $a_block);

    if ($new_a_block !== null) {
        $content = substr_replace($content, $new_a_block, $a_start_pos, $a_end_pos - $a_start_pos);
        return [true, $content];
    }
    return [false, $content];
}

function process_sidebar_file($path, $icon_paths) {
    echo "Updating submenu icons in $path...\n";
    $content = file_get_contents($path);
    if ($content === false) {
        echo "Failed to read $path\n";
        return;
    }

    $updated_count = 0;
    foreach ($icon_paths as $text => $new_path_content) {
        list($success, $new_content) = update_submenu_icon_in_content($content, $text, $new_path_content);
        if ($success) {
            $content = $new_content;
            $updated_count++;
        }
    }

    if (file_put_contents($path, $content) !== false) {
        echo "Successfully updated $updated_count icons in $path\n";
    } else {
        echo "Failed to write $path\n";
    }
}

process_sidebar_file('resources/views/vendor/tyro-dashboard/partials/admin-sidebar.blade.php', $icon_paths);
process_sidebar_file('resources/views/vendor/tyro-dashboard/partials/user-sidebar.blade.php', $icon_paths);
