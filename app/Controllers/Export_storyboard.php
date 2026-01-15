<?php

namespace App\Controllers;

use App\Controllers\Security_Controller;

class Export_storyboard extends Security_Controller {

    private $storyboardFontPath = null;
    private $storyboardLatinFontPath = null;

    function __construct() {
        parent::__construct();
        $this->access_only_team_members();

        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }
        
        // Pre-load models to avoid repeated loading
        try {
            $this->Projects_model = model('App\Models\Projects_model');
            $this->Storyboards_model = model('App\Models\Storyboards_model');
            $this->Scene_headings_model = model('App\Models\Scene_headings_model');
            
            error_log("Export_storyboard: Models loaded successfully");
        } catch (Exception $e) {
            error_log("Export_storyboard: Error loading models: " . $e->getMessage());
            error_log("Export_storyboard: Stack trace: " . $e->getTraceAsString());
        }
    }

    function index() {
        // Check if user has access to storyboard functionality
        // Remove module check if storyboard module doesn't exist in your system
        // $this->check_module_availability("module_storyboard");
        
        // Get project and sub-project from URL parameters
        $project_id = $this->request->getGet('project_id');
        $sub_project_id = $this->request->getGet('sub_project_id');
        
        // Get projects dropdown - only projects with storyboards
        try {
            $projects = $this->Projects_model->get_all()->getResult();
        } catch (Exception $e) {
            error_log("Export_storyboard: Error loading projects: " . $e->getMessage());
            $projects = array();
        }
        $projects_dropdown = array("" => "- " . app_lang('select_project') . " -");
        foreach ($projects as $project) {
            $projects_dropdown[$project->id] = $project->title;
        }
        
        $view_data['page_type'] = "full";
        $view_data['projects_dropdown'] = $projects_dropdown;
        $view_data['selected_project_id'] = $project_id;
        $view_data['selected_sub_project_id'] = $sub_project_id;
        
        return $this->template->rander("export_storyboard/index", $view_data);
    }

    // Get sub-projects for a given project
    function get_sub_projects() {
        $project_id = $this->request->getPost('project_id');
        
        error_log("Export_storyboard: get_sub_projects called with project_id: " . ($project_id ?: 'empty'));
        
        if (!$project_id) {
            echo '<option value="">-</option>';
            return;
        }

        try {
            // Check if Storyboards_model is available
            if (!isset($this->Storyboards_model)) {
                error_log("Export_storyboard: Storyboards_model not available, loading...");
                $this->Storyboards_model = model('App\Models\Storyboards_model');
            }
            
            // Get distinct sub-project IDs for this project (exclude deleted)
            $sub_projects_query = $this->Storyboards_model
                ->select('sub_storyboard_project_id AS sub_project_id')
                ->where('project_id', $project_id)
                ->where('sub_storyboard_project_id IS NOT NULL', null, false)
                ->where('sub_storyboard_project_id !=', '')
                ->where('sub_storyboard_project_id !=', '0')
                ->where('deleted', 0)
                ->groupBy('sub_storyboard_project_id')
                ->orderBy('sub_storyboard_project_id', 'ASC')
                ->get();

            $sub_projects = $sub_projects_query->getResult();
            
            error_log("Export_storyboard: Found " . count($sub_projects) . " sub-projects for project " . $project_id);
            
            // Use simple text instead of app_lang to avoid potential issues
            $options = '<option value="">- All Sub-Projects -</option>';
            
            foreach ($sub_projects as $sub_project) {
                $sub_project_value = $sub_project->sub_project_id;
                if (!empty($sub_project_value)) {
                    $options .= '<option value="' . htmlspecialchars($sub_project_value) . '">Sub-Project ' . htmlspecialchars($sub_project_value) . '</option>';
                }
            }
            
            echo $options;
            
        } catch (Exception $e) {
            error_log("Export_storyboard: Get sub-projects error: " . $e->getMessage());
            error_log("Export_storyboard: Stack trace: " . $e->getTraceAsString());
            echo '<option value="">Error loading sub-projects</option>';
        }
    }

    // Get storyboard data for export
    function get_storyboard_data() {
        $project_id = $this->request->getPost('project_id');
        $sub_project_id = $this->request->getPost('sub_project_id');
        
        if (!$project_id) {
            echo json_encode(array("success" => false, "message" => "Project ID is required"));
            return;
        }

        try {
            error_log("Export_storyboard: get_storyboard_data - Loading project " . $project_id . ", sub_project " . ($sub_project_id ?: 'none'));
            
            // Check if models are available
            if (!isset($this->Projects_model) || !isset($this->Storyboards_model) || !isset($this->Scene_headings_model)) {
                error_log("Export_storyboard: Models not available, reloading...");
                $this->Projects_model = model('App\Models\Projects_model');
                $this->Storyboards_model = model('App\Models\Storyboards_model');
                $this->Scene_headings_model = model('App\Models\Scene_headings_model');
            }
            
            // Get project info
            $project = $this->Projects_model->get_one($project_id);
            if (!$project || !$project->id) {
                error_log("Export_storyboard: Project not found: " . $project_id);
                echo json_encode(array("success" => false, "message" => "Project not found"));
                return;
            }

            // Build query conditions (exclude deleted records)
            $scene_headings_query = $this->Scene_headings_model
                ->where('project_id', $project_id)
                ->where('deleted', 0);
            $storyboards_query = $this->Storyboards_model
                ->where('project_id', $project_id)
                ->where('deleted', 0);

            if (!empty($sub_project_id)) {
                $scene_headings_query->where('sub_storyboard_project_id', $sub_project_id);
                $storyboards_query->where('sub_storyboard_project_id', $sub_project_id);
            }

            // Get scene headings
            $scene_headings = $scene_headings_query->orderBy('sort_order', 'ASC')->get()->getResult();

            // Get storyboards
            $storyboards = $storyboards_query->orderBy('sort_order', 'ASC')->get()->getResult();

            // Organize data by scene headings
            $organized_data = array();
            
            // Add scenes with headings
            foreach ($scene_headings as $heading) {
                $heading_storyboards = array();
                foreach ($storyboards as $storyboard) {
                    if ($storyboard->scene_heading_id == $heading->id) {
                        $heading_storyboards[] = $storyboard;
                    }
                }
                
                $organized_data[] = array(
                    'type' => 'heading',
                    'heading' => $heading,
                    'storyboards' => $heading_storyboards
                );
            }

            // Add unorganized scenes
            $unorganized_storyboards = array();
            foreach ($storyboards as $storyboard) {
                if (!$storyboard->scene_heading_id) {
                    $unorganized_storyboards[] = $storyboard;
                }
            }

            if (!empty($unorganized_storyboards)) {
                $organized_data[] = array(
                    'type' => 'unorganized',
                    'heading' => (object)array('title' => 'Unorganized Scenes'),
                    'storyboards' => $unorganized_storyboards
                );
            }

            echo json_encode(array(
                "success" => true,
                "project" => $project,
                "data" => $organized_data
            ));

        } catch (Exception $e) {
            error_log("Export storyboard data error: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error loading storyboard data"));
        }
    }

    // Export to PNG
    function export_png() {
        // $this->check_module_availability("module_storyboard");
        
        $project_id = $this->request->getPost('project_id');
        $sub_project_id = $this->request->getPost('sub_project_id');
        $selected_headings = $this->request->getPost('selected_headings');
        $selected_scenes = $this->request->getPost('selected_scenes');
        $export_options = $this->request->getPost('export_options');

        if (!$project_id) {
            echo json_encode(array("success" => false, "message" => "Project ID is required"));
            return;
        }

        try {
            $export_options = $this->normalize_export_options($export_options);

            // Get export data
            $export_data = $this->prepare_export_data($project_id, $sub_project_id, $selected_headings, $selected_scenes);
            
            if (empty($export_data['storyboards'])) {
                echo json_encode(array("success" => false, "message" => "No storyboards selected for export"));
                return;
            }

            // Generate PNG images
            $png_files = $this->generate_png($export_data, $export_options);
            
            echo json_encode(array(
                "success" => true,
                "files" => $png_files,
                "message" => "PNG files exported successfully"
            ));

        } catch (\Throwable $e) {
            error_log("PNG export error: " . $e->getMessage());
            echo json_encode(array("success" => false, "message" => "Error generating PNG: " . $e->getMessage()));
        }
    }

    // Prepare export data
    private function prepare_export_data($project_id, $sub_project_id, $selected_headings, $selected_scenes) {
        // Get project info
        $project = $this->Projects_model->get_one($project_id);
        
        $headings_data = array();
        $storyboard_map = array();

        $selected_headings = $this->filter_id_string($selected_headings);
        $selected_scenes = $this->filter_id_string($selected_scenes);

        // Process selected headings
        if (!empty($selected_headings)) {
            foreach ($selected_headings as $heading_id) {
                $heading = $this->Scene_headings_model->get_one($heading_id);
                if ($heading->id) {
                    $options = array(
                        'project_id' => $project_id,
                        'scene_heading_id' => $heading_id,
                        'deleted' => 0
                    );
                    if (!empty($sub_project_id)) {
                        $options['sub_storyboard_project_id'] = $sub_project_id;
                    }

                    $heading_storyboards = array();
                    $heading_result = $this->Storyboards_model->get_details($options);
                    if ($heading_result) {
                        $heading_storyboards = $heading_result->getResult();
                    }

                    $headings_data[] = array(
                        'heading' => $heading,
                        'storyboards' => $heading_storyboards
                    );

                    foreach ($heading_storyboards as $heading_storyboard) {
                        $storyboard_map[$heading_storyboard->id] = $heading_storyboard;
                    }
                }
            }
        }

        // Process selected individual scenes (exclude deleted)
        if (!empty($selected_scenes)) {
            foreach ($selected_scenes as $scene_id) {
                $storyboard = $this->Storyboards_model
                    ->where('id', $scene_id)
                    ->where('deleted', 0)
                    ->get()
                    ->getRow();
                if ($storyboard && $storyboard->id) {
                    $storyboard_map[$storyboard->id] = $storyboard;
                }
            }
        }

        return array(
            'project' => $project,
            'storyboards' => array_values($storyboard_map),
            'headings_data' => $headings_data
        );
    }

    private function filter_id_string($ids) {
        if (empty($ids)) {
            return array();
        }

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $clean_ids = array();
        foreach ($ids as $id) {
            $trimmed = trim($id);
            if ($trimmed !== '' && ctype_digit($trimmed)) {
                $clean_ids[] = (int)$trimmed;
            }
        }

        return array_unique($clean_ids);
    }

    private function normalize_export_options($options) {
        $defaults = array(
            'include_images' => true,
            'include_descriptions' => true,
            'include_notes' => true,
            'include_camera_info' => true
        );

        if (empty($options) || !is_array($options)) {
            return $defaults;
        }

        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $options)) {
                $defaults[$key] = $this->to_boolean($options[$key]);
            }
        }

        return $defaults;
    }

    private function to_boolean($value) {
        if (is_bool($value)) {
            return $value;
        }

        $lower = strtolower((string)$value);
        return in_array($lower, array('1', 'true', 'yes', 'on'), true);
    }

    private function ensure_font_paths() {
        if ($this->storyboardFontPath === null) {
            $primaryCandidates = array(
                getenv('STORYBOARD_EXPORT_FONT') ?: null,
                FCPATH . 'assets/fonts/NotoSansThai-Regular.ttf',
                FCPATH . 'assets/fonts/THSarabunNew.ttf',
                FCPATH . 'files/fonts/NotoSansThai-Regular.ttf',
                FCPATH . 'files/fonts/THSarabunNew.ttf',
                FCPATH . 'files/fonts/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/freefont/FreeSans.ttf'
            );

            foreach ($primaryCandidates as $candidate) {
                if (!empty($candidate) && file_exists($candidate) && is_readable($candidate)) {
                    $this->storyboardFontPath = realpath($candidate) ?: $candidate;
                    error_log('Export_storyboard: Using primary font for PNG export: ' . $this->storyboardFontPath);
                    break;
                }
            }

            if ($this->storyboardFontPath === null) {
                $this->storyboardFontPath = '';
                error_log('Export_storyboard: No UTF-8 font found for storyboard export. Characters may not render.');
            }
        }

        if ($this->storyboardLatinFontPath === null) {
            $latinCandidates = array(
                getenv('STORYBOARD_EXPORT_FONT_LATIN') ?: null,
                FCPATH . 'assets/fonts/NotoSans-Regular.ttf',
                FCPATH . 'assets/fonts/DejaVuSans.ttf',
                FCPATH . 'files/fonts/NotoSans-Regular.ttf',
                FCPATH . 'files/fonts/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/freefont/FreeSans.ttf'
            );

            foreach ($latinCandidates as $candidate) {
                if (!empty($candidate) && file_exists($candidate) && is_readable($candidate)) {
                    $this->storyboardLatinFontPath = realpath($candidate) ?: $candidate;
                    if ($this->storyboardLatinFontPath !== $this->storyboardFontPath) {
                        error_log('Export_storyboard: Using latin fallback font: ' . $this->storyboardLatinFontPath);
                    }
                    break;
                }
            }

            if ($this->storyboardLatinFontPath === null) {
                $this->storyboardLatinFontPath = $this->storyboardFontPath ?: '';
            }
        }
    }

    private function get_export_font_path() {
        $this->ensure_font_paths();
        if ($this->storyboardFontPath !== '') {
            return $this->storyboardFontPath;
        }
        return $this->storyboardLatinFontPath !== '' ? $this->storyboardLatinFontPath : null;
    }

    private function get_latin_font_path() {
        $this->ensure_font_paths();
        if ($this->storyboardLatinFontPath !== '') {
            return $this->storyboardLatinFontPath;
        }
        return $this->storyboardFontPath !== '' ? $this->storyboardFontPath : null;
    }

    private function contains_thai_characters($text) {
        return $text !== '' && preg_match('/[\x{0E00}-\x{0E7F}]/u', $text);
    }

    private function contains_latin_characters($text) {
        return $text !== '' && preg_match('/[A-Za-z0-9]/', $text);
    }

    private function is_ascii_char($char) {
        return $char !== '' && strlen($char) === 1 && ord($char) <= 0x7F;
    }

    private function select_wrap_font($text) {
        $primary = $this->get_export_font_path();
        $latin = $this->get_latin_font_path();

        if ($primary && $this->contains_thai_characters($text)) {
            return $primary;
        }

        if ($latin && $this->contains_latin_characters($text)) {
            return $latin;
        }

        return $primary ?: $latin;
    }

    private function split_text_segments($text) {
        $this->ensure_font_paths();
        $primary = $this->storyboardFontPath !== '' ? $this->storyboardFontPath : null;
        $latin = $this->storyboardLatinFontPath !== '' ? $this->storyboardLatinFontPath : null;

        $segments = array();

        if ($text === '') {
            $segments[] = array('text' => '', 'font' => $primary ?: $latin, 'is_ttf' => (bool) ($primary ?: $latin));
            return $segments;
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            $chars = str_split($text);
        }

        $buffer = '';
        $currentType = null;

        foreach ($chars as $char) {
            $type = $this->is_ascii_char($char) ? 'latin' : 'primary';

            if ($type !== $currentType && $buffer !== '') {
                $segments[] = $this->create_text_segment($buffer, $currentType, $primary, $latin);
                $buffer = '';
            }

            $buffer .= $char;
            $currentType = $type;
        }

        if ($buffer !== '') {
            $segments[] = $this->create_text_segment($buffer, $currentType, $primary, $latin);
        }

        return $segments;
    }

    private function create_text_segment($text, $type, $primary, $latin) {
        $font = null;

        if ($type === 'latin') {
            $font = $latin ?: $primary;
        } else {
            $font = $primary ?: $latin;
        }

        return array(
            'text' => $text,
            'font' => $font,
            'is_ttf' => !empty($font)
        );
    }

    private function get_text_width($font_path, $font_size, $text) {
        $box = @imagettfbbox($font_size, 0, $font_path, $text);
        if ($box === false || count($box) < 8) {
            return strlen((string) $text) * max(1, (int) round($font_size * 0.5));
        }
        $minX = min($box[0], $box[2], $box[4], $box[6]);
        $maxX = max($box[0], $box[2], $box[4], $box[6]);
        return (int) ($maxX - $minX);
    }

    private function wrap_text_for_ttf($text, $font_path, $font_size, $max_width) {
        $clean = trim((string) $text);
        if ($clean === '') {
            return array('');
        }

        $max_width = max(1, (int) $max_width);
        $paragraphs = preg_split('/\r?\n/u', $clean);
        if ($paragraphs === false) {
            $paragraphs = array($clean);
        }
        $lines = array();
        $has_mb = function_exists('mb_strlen') && function_exists('mb_substr');

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                $lines[] = '';
                continue;
            }

            $current_line = '';
            $length = $has_mb ? mb_strlen($paragraph, 'UTF-8') : strlen($paragraph);

            for ($i = 0; $i < $length; $i++) {
                $char = $has_mb ? mb_substr($paragraph, $i, 1, 'UTF-8') : $paragraph[$i];

                if ($char === "\r" || $char === "\n") {
                    if ($current_line !== '') {
                        $lines[] = $current_line;
                        $current_line = '';
                    }
                    continue;
                }

                $candidate = $current_line . $char;
                $candidate_width = $this->get_text_width($font_path, $font_size, $candidate);

                if ($candidate_width > $max_width && $current_line !== '') {
                    $lines[] = $current_line;
                    $current_line = $char;
                } else {
                    $current_line = $candidate;
                }
            }

            if ($current_line !== '') {
                $lines[] = $current_line;
            }
        }

        return !empty($lines) ? $lines : array('');
    }

    private function draw_simple_text($image, $text, $x, $y, $color, $font_size = 14, $bold = false) {
        $segments = $this->split_text_segments((string) $text);
        $x_offset = $x;
        $baseline = $y + $font_size;
        $used_ttf = false;
        $gd_font = max(1, min(5, (int) round($font_size / 5)));
        $gd_line_height = imagefontheight($gd_font) + 2;

        foreach ($segments as $segment) {
            $segment_text = $segment['text'];

            if ($segment['is_ttf']) {
                $font_path = $segment['font'];
                if ($font_path) {
                    imagettftext($image, $font_size, 0, $x_offset, $baseline, $color, $font_path, $segment_text);
                    if ($bold) {
                        imagettftext($image, $font_size, 0, $x_offset + 1, $baseline, $color, $font_path, $segment_text);
                    }
                    $segment_width = max(0, $this->get_text_width($font_path, $font_size, $segment_text));
                    $x_offset += $segment_width;
                    $used_ttf = true;
                    continue;
                }
            }

            $segment_width = strlen($segment_text) * imagefontwidth($gd_font);
            imagestring($image, $gd_font, $x_offset, $y, $segment_text, $color);
            if ($bold) {
                imagestring($image, $gd_font, $x_offset + 1, $y, $segment_text, $color);
            }
            $x_offset += $segment_width;
        }

        if ($used_ttf) {
            return $y + (int) ceil($font_size * 1.35);
        }

        return $y + $gd_line_height;
    }

    private function resolve_storyboard_image_path($storyboard) {
        $candidates = array();

        // Check common storyboard image fields
        $image_fields = array('storyboard_image', 'image', 'image_path', 'file_name', 'filename');
        foreach ($image_fields as $field) {
            if (!empty($storyboard->$field)) {
                $candidates[] = $storyboard->$field;
            }
        }

        // Check frame data (this is the main field for new storyboards)
        if (!empty($storyboard->frame)) {
            $frame_data = $this->decode_frame_payload($storyboard->frame);
            if (is_array($frame_data)) {
                // Priority to file_name as that's how new storyboards store it
                if (!empty($frame_data['file_name'])) {
                    $candidates[] = $frame_data['file_name'];
                    // Also try with the full path structure
                    $candidates[] = 'files/storyboard_frames/' . $frame_data['file_name'];
                }
                
                // Check other possible keys
                $possible_keys = array('file_path', 'path', 'filename', 'full_path', 'local_path', 'image_path');
                foreach ($possible_keys as $key) {
                    if (!empty($frame_data[$key])) {
                        $candidates[] = $frame_data[$key];
                    }
                }

                if (!empty($frame_data['url'])) {
                    $candidates[] = $frame_data['url'];
                }
            } elseif (is_string($frame_data) && $frame_data !== '') {
                $candidates[] = $frame_data;
            }
        }

        // Check additional properties
        $extra_props = array('frame_file', 'frame_filename', 'frame_path', 'frame_url', 'file_path');
        foreach ($extra_props as $prop) {
            if (!empty($storyboard->$prop)) {
                $candidates[] = $storyboard->$prop;
            }
        }

        // Log what we're looking for
        error_log("Export_storyboard: Looking for image for storyboard ID " . ($storyboard->id ?? 'unknown') . ". Candidates: " . implode(', ', $candidates));

        foreach ($candidates as $candidate) {
            $resolved = $this->resolve_to_existing_path($candidate);
            if ($resolved) {
                error_log("Export_storyboard: Found image at: " . $resolved);
                return $resolved;
            }
        }

        error_log("Export_storyboard: No image found for storyboard ID " . ($storyboard->id ?? 'unknown'));
        return null;
    }

    private function decode_frame_payload($payload) {
        if (is_array($payload)) {
            return $payload;
        }

        if (!is_string($payload)) {
            return null;
        }

        $payload = trim($payload);
        if ($payload === '') {
            return null;
        }

        $unserialized = @unserialize($payload);
        if ($unserialized !== false || $payload === 'b:0;') {
            return $unserialized;
        }

        $json = json_decode($payload, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return $payload;
    }

    private function resolve_to_existing_path($candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            return null;
        }

        $candidate = trim($candidate);

        if (preg_match('/^https?:\/\//i', $candidate)) {
            return null; // Remote URLs are not supported for exports
        }

        $paths_to_try = array();

        if ($this->is_absolute_path($candidate) && file_exists($candidate)) {
            return realpath($candidate);
        }

        $clean = ltrim($candidate, '/');
        
        // Priority order: new storyboard location first, then legacy locations
        $paths_to_try[] = $candidate;
        $paths_to_try[] = 'files/storyboard_frames/' . $clean;
        $paths_to_try[] = 'files/timeline_files/' . $clean;  // Legacy location
        $paths_to_try[] = 'files/storyboard_images/' . $clean;
        $paths_to_try[] = 'files/project_files/' . $clean;
        $paths_to_try[] = 'files/' . $clean;
        $paths_to_try[] = $clean;
        
        // Also try with common image subdirectories
        $paths_to_try[] = 'files/storyboard_frames/images/' . $clean;
        $paths_to_try[] = 'files/timeline_files/images/' . $clean;
        
        // Add absolute paths based on your server structure
        $paths_to_try[] = FCPATH . 'files/storyboard_frames/' . $clean;
        $paths_to_try[] = FCPATH . 'files/timeline_files/' . $clean;

        error_log("Export_storyboard: Checking paths for candidate: " . $candidate);
        
        foreach ($paths_to_try as $path) {
            error_log("Export_storyboard: Checking path: " . $path);
            if (file_exists($path)) {
                error_log("Export_storyboard: Found file at: " . $path);
                return realpath($path);
            }
            $full = FCPATH . ltrim($path, '/');
            error_log("Export_storyboard: Checking full path: " . $full);
            if (file_exists($full)) {
                error_log("Export_storyboard: Found file at: " . $full);
                return realpath($full);
            }
        }

        error_log("Export_storyboard: File not found in any of these paths: " . implode(', ', $paths_to_try));
        error_log("Export_storyboard: FCPATH is: " . FCPATH);
        return null;
    }

    private function is_absolute_path($path) {
        if (preg_match('/^[a-zA-Z]:\\\\/', $path)) {
            return true; // Windows drive path
        }

        return strpos($path, '/') === 0;
    }

    private function shorten_text($text, $length = 90) {
        $clean = strip_tags((string)$text);
        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($clean, 0, $length, '...');
        }

        if (strlen($clean) <= $length) {
            return $clean;
        }

        return substr($clean, 0, max(0, $length - 3)) . '...';
    }

    private function get_storyboard_title($storyboard, $fallbackIndex = null) {
        // Based on your table structure, check content field first
        $candidates = array(
            isset($storyboard->content) ? $storyboard->content : null,
            isset($storyboard->scene_title) ? $storyboard->scene_title : null,
            isset($storyboard->title) ? $storyboard->title : null,
            isset($storyboard->header) ? $storyboard->header : null,
            isset($storyboard->name) ? $storyboard->name : null
        );

        foreach ($candidates as $candidate) {
            if (!empty($candidate)) {
                // Limit title length for display
                $title = strip_tags((string)$candidate);
                return strlen($title) > 50 ? substr($title, 0, 47) . '...' : $title;
            }
        }

        if (isset($storyboard->shot) && $storyboard->shot !== '') {
            return 'Shot ' . $storyboard->shot;
        }

        if ($fallbackIndex !== null) {
            return 'Scene ' . ($fallbackIndex + 1);
        }

        if (isset($storyboard->id)) {
            return 'Scene ' . $storyboard->id;
        }

        return 'Scene';
    }

    private function calculate_total_duration($storyboards) {
        $total_seconds = 0;

        foreach ($storyboards as $storyboard) {
            if (empty($storyboard->duration)) {
                continue;
            }
            $duration = trim($storyboard->duration);

            if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $duration, $matches)) {
                $hours = (int)$matches[1];
                $minutes = (int)$matches[2];
                $seconds = (int)$matches[3];
                $total_seconds += ($hours * 3600) + ($minutes * 60) + $seconds;
                continue;
            }

            if (preg_match('/^(\d{1,2}):(\d{2})$/', $duration, $matches)) {
                $minutes = (int)$matches[1];
                $seconds = (int)$matches[2];
                $total_seconds += ($minutes * 60) + $seconds;
                continue;
            }

            if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $duration, $matches)) {
                $value = (float)$matches[1];
                if (stripos($duration, 'min') !== false || stripos($duration, 'm') !== false) {
                    $total_seconds += $value * 60;
                } elseif (stripos($duration, 'h') !== false) {
                    $total_seconds += $value * 3600;
                } else {
                    $total_seconds += $value;
                }
            }
        }

        if ($total_seconds <= 0) {
            return '';
        }

        $hours = floor($total_seconds / 3600);
        $minutes = floor(($total_seconds % 3600) / 60);
        $seconds = round($total_seconds % 60);

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    // Generate PNG images
    private function generate_png($export_data, $export_options) {
        $png_files = array();
        $export_dir = FCPATH . 'files/exports/';

        if (!is_dir($export_dir)) {
            if (!mkdir($export_dir, 0755, true)) {
                throw new \RuntimeException('Unable to create export directory: ' . $export_dir);
            }
        }

        if (!is_writable($export_dir)) {
            throw new \RuntimeException('Export directory is not writable: ' . $export_dir);
        }

        $filename = 'storyboard_combined_' . $export_data['project']->id . '_' . date('Y-m-d_H-i-s') . '.png';
        $file_path = $export_dir . $filename;

        $this->create_combined_storyboard_png($export_data, $file_path, $export_options);

        $png_files[] = array(
            'filename' => $filename,
            'path' => 'files/exports/' . $filename,
            'scene_title' => $export_data['project']->title ?? 'Storyboard Export'
        );

        return $png_files;
    }

    // Create a single PNG image containing all selected storyboards
    private function create_combined_storyboard_png($export_data, $file_path, $export_options) {
        $storyboards = $export_data['storyboards'];

        if (empty($storyboards)) {
            throw new \RuntimeException('No storyboard data available for PNG export.');
        }

        if (!function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('PNG export requires the GD extension with PNG support.');
        }

        $columns = min(5, max(1, count($storyboards)));
        $card_width = 360;
        $card_height = 520;
        $card_gap = 20;
        $margin = 40;

        $rows = (int) ceil(count($storyboards) / $columns);
        $canvas_width = ($margin * 2) + ($columns * $card_width) + (($columns - 1) * $card_gap);
        $canvas_height = ($margin * 2) + ($rows * $card_height) + (($rows - 1) * $card_gap) + 80;

        $image = imagecreatetruecolor($canvas_width, $canvas_height);

        $bg_color = imagecolorallocate($image, 245, 247, 250);
        $card_bg = imagecolorallocate($image, 255, 255, 255);
        $card_border = imagecolorallocate($image, 210, 214, 220);
        $text_primary = imagecolorallocate($image, 31, 37, 51);
        $text_secondary = imagecolorallocate($image, 90, 100, 120);
        $accent_color = imagecolorallocate($image, 79, 130, 224);

        imagefill($image, 0, 0, $bg_color);

        $title = $export_data['project']->title ?? 'Storyboard Export';
        $this->draw_simple_text($image, $title, $margin, 10, $text_primary, 20, true);

        $timestamp = 'Exported: ' . date('Y-m-d H:i:s');
        $this->draw_simple_text($image, $timestamp, $margin, 38, $text_secondary, 12);

        foreach ($storyboards as $index => $storyboard) {
            $row = (int) floor($index / $columns);
            $column = $index % $columns;

            $card_x = $margin + $column * ($card_width + $card_gap);
            $card_y = $margin + 60 + $row * ($card_height + $card_gap);

            imagefilledrectangle($image, $card_x, $card_y, $card_x + $card_width, $card_y + $card_height, $card_bg);
            imagerectangle($image, $card_x, $card_y, $card_x + $card_width, $card_y + $card_height, $card_border);

            $shot_label = 'Shot ' . ($storyboard->shot ?? ($index + 1));
            $this->draw_simple_text($image, $shot_label, $card_x + 12, $card_y + 8, $accent_color, 18, true);

            $title_text = $this->get_storyboard_title($storyboard, $index);
            $this->draw_text_block($image, strtoupper($title_text), 18, $card_x + 12, $card_y + 38, $text_primary, $card_width - 24, 24, true);

            if ($export_options['include_images']) {
                $storyboard_image_path = $this->resolve_storyboard_image_path($storyboard);
                if ($storyboard_image_path && file_exists($storyboard_image_path)) {
                    $this->render_storyboard_image($image, $storyboard_image_path, $card_x + 12, $card_y + 70, $card_width - 24, 200);
                } else {
                    $this->draw_placeholder_image($image, $card_x + 12, $card_y + 70, $card_width - 24, 200, $card_border, $text_secondary);
                }
            } else {
                $this->draw_placeholder_image($image, $card_x + 12, $card_y + 70, $card_width - 24, 200, $card_border, $text_secondary);
            }

            $content_y = $card_y + 280;

            if ($export_options['include_descriptions']) {
                $description = '';
                if (!empty($storyboard->content)) {
                    $description = $storyboard->content;
                } elseif (!empty($storyboard->scene_description)) {
                    $description = $storyboard->scene_description;
                } elseif (!empty($storyboard->dialogues)) {
                    $description = $storyboard->dialogues;
                }
                if (!empty($description)) {
                    $content_y = $this->draw_labeled_block($image, 'Description', $description, $card_x + 12, $content_y, $text_secondary, $text_primary, $card_width - 24);
                }
            }

            if ($export_options['include_notes']) {
                $notes = $storyboard->note ?? $storyboard->notes ?? '';
                if (!empty($notes)) {
                    $content_y = $this->draw_labeled_block($image, 'Notes', $notes, $card_x + 12, $content_y, $text_secondary, $text_primary, $card_width - 24);
                }
            }

            if ($export_options['include_camera_info']) {
                $meta_lines = array();
                if (!empty($storyboard->camera_angle)) {
                    $meta_lines[] = 'Camera: ' . $storyboard->camera_angle;
                }
                if (!empty($storyboard->shot_type)) {
                    $meta_lines[] = 'Shot: ' . $storyboard->shot_type;
                }
                if (!empty($storyboard->shot_size)) {
                    $meta_lines[] = 'Size: ' . $storyboard->shot_size;
                }
                if (!empty($storyboard->movement)) {
                    $meta_lines[] = 'Movement: ' . $storyboard->movement;
                }
                if (!empty($storyboard->duration)) {
                    $meta_lines[] = 'Duration: ' . $storyboard->duration;
                }

                if (!empty($meta_lines)) {
                    $meta_text = implode("\n", $meta_lines);
                    $content_y = $this->draw_labeled_block($image, 'Details', $meta_text, $card_x + 12, $content_y, $text_secondary, $text_primary, $card_width - 24);
                }
            }
        }

        if (!imagepng($image, $file_path)) {
            imagedestroy($image);
            throw new \RuntimeException('Failed to write combined PNG file: ' . $file_path);
        }

        imagedestroy($image);
    }

    private function draw_text_block($image, $text, $font_size, $x, $y, $color, $max_width, $line_height = null, $bold = false) {
        $wrap_font = $this->select_wrap_font($text);

        if ($wrap_font) {
            $lines = $this->wrap_text_for_ttf(strip_tags((string) $text), $wrap_font, $font_size, $max_width);
            $line_height = $line_height ? max($line_height, (int) ceil($font_size * 1.35)) : (int) ceil($font_size * 1.35);

            foreach ($lines as $line) {
                if ($line === '') {
                    $y += $line_height;
                    continue;
                }

                $segments = $this->split_text_segments($line);
                $baseline = $y + $font_size;
                $x_offset = $x;

                foreach ($segments as $segment) {
                    $segment_text = $segment['text'];

                    if ($segment['is_ttf']) {
                        $font_path = $segment['font'];
                        if ($font_path) {
                            imagettftext($image, $font_size, 0, $x_offset, $baseline, $color, $font_path, $segment_text);
                            if ($bold) {
                                imagettftext($image, $font_size, 0, $x_offset + 1, $baseline, $color, $font_path, $segment_text);
                            }
                            $x_offset += max(0, $this->get_text_width($font_path, $font_size, $segment_text));
                            continue;
                        }
                    }

                    $gd_font = max(1, min(5, (int) round($font_size / 5)));
                    $segment_width = strlen($segment_text) * imagefontwidth($gd_font);
                    imagestring($image, $gd_font, $x_offset, $y, $segment_text, $color);
                    if ($bold) {
                        imagestring($image, $gd_font, $x_offset + 1, $y, $segment_text, $color);
                    }
                    $x_offset += $segment_width;
                }

                $y += $line_height;
            }

            return $y;
        }

        $gd_font = max(1, min(5, (int) round($font_size / 5)));
        $char_width = imagefontwidth($gd_font) ?: 6;
        $line_height = $line_height ?: imagefontheight($gd_font) + 4;
        $wrap_length = max(1, (int) floor($max_width / max(1, $char_width)));
        $lines = explode("
", wordwrap(strip_tags((string) $text), $wrap_length, "
", true));

        foreach ($lines as $line) {
            if ($bold) {
                imagestring($image, $gd_font, $x, $y, $line, $color);
                imagestring($image, $gd_font, $x + 1, $y, $line, $color);
            } else {
                imagestring($image, $gd_font, $x, $y, $line, $color);
            }
            $y += $line_height;
        }

        return $y;
    }

    private function draw_labeled_block($image, $label, $text, $x, $y, $label_color, $text_color, $max_width) {
        $y = $this->draw_simple_text($image, strtoupper($label), $x, $y, $label_color, 12, true);
        $y = $this->draw_text_block($image, $this->shorten_text($text, 280), 12, $x, $y, $text_color, $max_width, 20);
        return $y + 8;
    }

    private function draw_placeholder_image($image, $x, $y, $width, $height, $border_color, $text_color) {
        imagerectangle($image, $x, $y, $x + $width, $y + $height, $border_color);
        $placeholder = 'No Image';
        $font_path = $this->get_export_font_path();

        if ($font_path) {
            $font_size = 12;
            $line_height = (int) ceil($font_size * 1.35);
            $text_width = $this->get_text_width($font_path, $font_size, $placeholder);
            $text_x = $x + (int) max(0, ($width - $text_width) / 2);
            $text_y = $y + (int) max(0, ($height - $line_height) / 2);
            $this->draw_simple_text($image, $placeholder, $text_x, $text_y, $text_color, $font_size);
            return;
        }

        $gd_font = 3;
        $text_width = strlen($placeholder) * imagefontwidth($gd_font);
        $text_x = $x + (int) (($width - $text_width) / 2);
        $text_y = $y + (int) (($height - imagefontheight($gd_font)) / 2);
        imagestring($image, $gd_font, max($x + 4, $text_x), max($y + 4, $text_y), $placeholder, $text_color);
    }

    private function render_storyboard_image($canvas, $source_path, $x, $y, $max_width, $max_height) {
        $contents = @file_get_contents($source_path);
        if ($contents === false) {
            return;
        }

        $image = @imagecreatefromstring($contents);
        if (!$image) {
            return;
        }

        $src_width = imagesx($image);
        $src_height = imagesy($image);

        if ($src_width <= 0 || $src_height <= 0) {
            imagedestroy($image);
            return;
        }

        $ratio = min($max_width / $src_width, $max_height / $src_height);
        $ratio = max($ratio, 0.01);

        $dest_width = (int) max(1, $src_width * $ratio);
        $dest_height = (int) max(1, $src_height * $ratio);

        $dest_x = $x + (int) (($max_width - $dest_width) / 2);
        $dest_y = $y + (int) (($max_height - $dest_height) / 2);

        imagecopyresampled($canvas, $image, $dest_x, $dest_y, 0, 0, $dest_width, $dest_height, $src_width, $src_height);
        imagedestroy($image);
    }

    // Test endpoint to check if controller is working
    function test() {
        try {
            $response = array(
                "success" => true,
                "message" => "Export storyboard controller is working",
                "timestamp" => date('Y-m-d H:i:s'),
                "models_loaded" => array(
                    "Projects_model" => isset($this->Projects_model),
                    "Storyboards_model" => isset($this->Storyboards_model),
                    "Scene_headings_model" => isset($this->Scene_headings_model)
                )
            );
            
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(array(
                "success" => false,
                "message" => "Error: " . $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ));
        }
    }

    // Debug method to check storyboard data and images
    function debug_storyboard() {
        $project_id = $this->request->getGet('project_id');
        $sub_project_id = $this->request->getGet('sub_project_id');
        
        if (!$project_id) {
            echo json_encode(array("error" => "project_id required"));
            return;
        }

        try {
            // Get storyboards with deleted filter
            $query = $this->Storyboards_model
                ->where('project_id', $project_id)
                ->where('deleted', 0);
                
            if (!empty($sub_project_id)) {
                $query->where('sub_storyboard_project_id', $sub_project_id);
            }
            
            $storyboards = $query->limit(5)->get()->getResult();
            
            $debug_data = array();
            foreach ($storyboards as $storyboard) {
                $image_path = $this->resolve_storyboard_image_path($storyboard);
                
                // Decode frame data for debugging
                $frame_debug = 'empty';
                if (!empty($storyboard->frame)) {
                    $frame_data = $this->decode_frame_payload($storyboard->frame);
                    if (is_array($frame_data)) {
                        $frame_debug = array(
                            'type' => 'array',
                            'keys' => array_keys($frame_data),
                            'file_name' => $frame_data['file_name'] ?? 'not set'
                        );
                    } else {
                        $frame_debug = array(
                            'type' => gettype($frame_data),
                            'value' => is_string($frame_data) ? substr($frame_data, 0, 100) : $frame_data
                        );
                    }
                }
                
                $debug_data[] = array(
                    'id' => $storyboard->id,
                    'title' => $storyboard->content ?? 'No content',
                    'shot' => $storyboard->shot ?? 'No shot',
                    'deleted' => $storyboard->deleted ?? 'not set',
                    'frame_debug' => $frame_debug,
                    'resolved_image_path' => $image_path,
                    'image_exists' => $image_path ? file_exists($image_path) : false
                );
            }
            
            echo json_encode(array(
                "success" => true,
                "project_id" => $project_id,
                "sub_project_id" => $sub_project_id,
                "storyboards_found" => count($storyboards),
                "debug_data" => $debug_data
            ), JSON_PRETTY_PRINT);
            
        } catch (Exception $e) {
            echo json_encode(array(
                "success" => false,
                "error" => $e->getMessage()
            ));
        }
    }

    // Download exported file
    function download() {
        $file_path = $this->request->getGet('file');
        
        if (!$file_path || !file_exists(FCPATH . $file_path)) {
            show_404();
            return;
        }
        
        $full_path = FCPATH . $file_path;
        $filename = basename($full_path);
        
        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($full_path));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Output file
        readfile($full_path);
        exit;
    }
}
