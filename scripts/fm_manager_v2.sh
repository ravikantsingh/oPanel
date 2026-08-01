#!/bin/bash
# /opt/panel/scripts/fm_manager.sh
# Executed by Python Daemon as root

PAYLOAD=$1
TASK_ID=$2
DOMAIN=$(echo "$PAYLOAD" | jq -r '.domain')
USERNAME=$(echo "$PAYLOAD" | jq -r '.username')
PHP_VER=$(echo "$PAYLOAD" | jq -r '.php_version')

FM_DIR="/home/$USERNAME/web/$DOMAIN/filemanager"
DOC_ROOT="/home/$USERNAME/web/$DOMAIN/public_html"
VHOST="/etc/nginx/sites-available/$DOMAIN.conf"
WEB_ROOT="/home/$USERNAME/web/$DOMAIN"

if [ ! -f "$VHOST" ]; then
    echo "Error: Nginx configuration for $DOMAIN not found."
    exit 1
fi

# 1. Provision Directory & Private Session Store
mkdir -p "$FM_DIR/sessions"
WEBHOOK_TOKEN=$(mysql -N -s -e "SELECT webhook_token FROM panel_core.users WHERE username='$USERNAME';")

# 2. Generate Safe Config
cat <<EOF > "$FM_DIR/config.php"
<?php
\$domain = '$DOMAIN';
\$doc_root = '$DOC_ROOT';
\$secret = '$WEBHOOK_TOKEN';
EOF

# 3. GENERATE STACKRIUM NATIVE FILE MANAGER
cat << 'EOF' > "$FM_DIR/index.php"
<?php
require 'config.php';

session_set_cookie_params(['path' => '/', 'domain' => '', 'samesite' => 'Lax']);
session_name('filemanager');
session_start();

if (isset($_GET['sso_t']) && isset($_GET['sso_h'])) {
    $expected = hash_hmac('sha256', $domain . '|' . $_GET['sso_t'], $secret);
    if (hash_equals($expected, $_GET['sso_h']) && (time() - $_GET['sso_t'] < 60)) {
        $_SESSION['filemanager']['logged'] = 'admin';
        session_write_close();
        $clean_url = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: " . $clean_url);
        exit;
    } else {
        die("<div style='background:#111;color:red;padding:20px;'>Stackrium SSO Error: Signature mismatch. Please relaunch from panel.</div>");
    }
}

if (empty($_SESSION['filemanager']['logged'])) {
    http_response_code(403);
    die("<div style='background:#111827;color:red;padding:20px;font-family:sans-serif;'>Stackrium Security: Access Denied. Session expired or missing. Please launch from panel.</div>");
}

$current_dir = $doc_root;
$rel_path = '';

if (isset($_GET['p'])) {
    $requested = trim($_GET['p'], '/');
    $test_path = realpath($doc_root . '/' . $requested);
    if ($test_path && strpos($test_path, $doc_root) === 0) {
        $current_dir = $test_path;
        $rel_path = $requested;
    }
}

$parent_dir = dirname($rel_path) === '.' ? '' : dirname($rel_path);

if (isset($_GET['dl'])) {
    $dl_path = realpath($doc_root . '/' . ltrim($_GET['dl'], '/'));
    if ($dl_path && strpos($dl_path, $doc_root) === 0 && is_file($dl_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($dl_path).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($dl_path));
        ob_clean();
        flush();
        readfile($dl_path);
        exit;
    }
}

if (isset($_POST['ajax_get_folders'])) {
    header('Content-Type: application/json');
    ob_clean();
    $req_path = realpath($doc_root . '/' . ltrim($_POST['ajax_get_folders'], '/'));
    $f_list = [];
    if ($req_path && strpos($req_path, $doc_root) === 0 && is_dir($req_path)) {
        $items = scandir($req_path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            if (is_dir($req_path . '/' . $item)) {
                $f_list[] = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
            }
        }
    }
    natcasesort($f_list);
    echo json_encode(array_values($f_list));
    exit;
}

if (isset($_GET['raw'])) {
    $raw_path = realpath($doc_root . '/' . ltrim($_GET['raw'], '/'));
    if ($raw_path && strpos($raw_path, $doc_root) === 0 && is_file($raw_path)) {
        $mime = mime_content_type($raw_path);
        header("Content-Type: $mime");
        ob_clean();
        flush();
        readfile($raw_path);
        exit;
    }
}

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $targets = isset($_POST['targets']) ? $_POST['targets'] : (isset($_POST['target']) ? [$_POST['target']] : []);
    
    $valid_targets = [];
    foreach ($targets as $t) {
        $path = realpath($doc_root . '/' . ltrim($t, '/'));
        if ($path && strpos($path, $doc_root) === 0 && $path !== $doc_root) {
            $valid_targets[] = $path;
        }
    }

    if ($action === 'save' && count($valid_targets) === 1 && is_file($valid_targets[0])) {
        if (file_put_contents($valid_targets[0], $_POST['content'] ?? '') !== false) {
            $msg = "File saved successfully."; $msg_type = "success";
        } else { $msg = "Error saving file."; $msg_type = "error"; }
    }
    
    if ($action === 'delete' && !empty($valid_targets)) {
        foreach ($valid_targets as $path) {
            is_dir($path) ? exec("rm -rf " . escapeshellarg($path)) : unlink($path);
        }
        $msg = count($valid_targets) . " item(s) deleted."; $msg_type = "success";
    }

    if ($action === 'mkdir') {
        $new = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['new_name']);
        if ($new && mkdir($current_dir . '/' . $new)) { $msg = "Folder created."; $msg_type = "success"; }
    }

    if ($action === 'mkfile') {
        $new = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['new_name']);
        if ($new && touch($current_dir . '/' . $new)) { $msg = "File created."; $msg_type = "success"; }
    }
    
    if ($action === 'rename' && count($valid_targets) === 1) {
        $new_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['new_name']);
        if ($new_name && rename($valid_targets[0], dirname($valid_targets[0]) . '/' . $new_name)) {
            $msg = "Renamed successfully."; $msg_type = "success";
        }
    }

    if ($action === 'copy' && !empty($valid_targets)) {
        $dest_base = $doc_root . '/' . ltrim($_POST['dest'], '/');
        if (count($valid_targets) === 1 && !is_dir($dest_base) && !str_ends_with($_POST['dest'], '/')) {
            if (strpos(realpath(dirname($dest_base)), $doc_root) === 0) {
                is_dir($valid_targets[0]) ? exec("cp -r " . escapeshellarg($valid_targets[0]) . " " . escapeshellarg($dest_base)) : copy($valid_targets[0], $dest_base);
            }
        } else {
            if (strpos(realpath($dest_base), $doc_root) === 0 && is_dir($dest_base)) {
                foreach ($valid_targets as $path) {
                    $final_dest = $dest_base . '/' . basename($path);
                    is_dir($path) ? exec("cp -r " . escapeshellarg($path) . " " . escapeshellarg($final_dest)) : copy($path, $final_dest);
                }
            }
        }
        $msg = "Copied successfully."; $msg_type = "success";
    }

    if ($action === 'move' && !empty($valid_targets)) {
        $dest_base = $doc_root . '/' . ltrim($_POST['dest'], '/');
        if (count($valid_targets) === 1 && !is_dir($dest_base) && !str_ends_with($_POST['dest'], '/')) {
            if (strpos(realpath(dirname($dest_base)), $doc_root) === 0) {
                rename($valid_targets[0], $dest_base);
            }
        } else {
            if (strpos(realpath($dest_base), $doc_root) === 0 && is_dir($dest_base)) {
                foreach ($valid_targets as $path) {
                    rename($path, $dest_base . '/' . basename($path));
                }
            }
        }
        $msg = "Moved successfully."; $msg_type = "success";
    }

    if ($action === 'chmod' && count($valid_targets) === 1) {
        $perms = octdec($_POST['perms']);
        if ($perms && chmod($valid_targets[0], $perms)) {
            $msg = "Permissions updated."; $msg_type = "success";
        }
    }

    if ($action === 'zip' && !empty($valid_targets)) {
        $zip_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['new_name']);
        if (!str_ends_with($zip_name, '.zip')) $zip_name .= '.zip';
        $zip_path = $current_dir . '/' . $zip_name;
        $cd_cmd = "cd " . escapeshellarg($doc_root) . " && ";
        $rel_targets = array_map(function($p) use ($doc_root) { return ltrim(substr($p, strlen($doc_root)), '/'); }, $valid_targets);
        exec($cd_cmd . "zip -r " . escapeshellarg($zip_path) . " " . implode(' ', array_map('escapeshellarg', $rel_targets)));
        $msg = "Archive created."; $msg_type = "success";
    }

    if ($action === 'extract' && !empty($valid_targets)) {
        foreach ($valid_targets as $path) {
            if (pathinfo($path, PATHINFO_EXTENSION) === 'zip') {
                exec("unzip -o " . escapeshellarg($path) . " -d " . escapeshellarg($current_dir));
            }
        }
        $msg = "Archive extracted."; $msg_type = "success";
    }

    if ($action === 'bulk_download' && !empty($valid_targets)) {
        $tmp_zip = tempnam(sys_get_temp_dir(), 'stack_fm_') . '.zip';
        $cd_cmd = "cd " . escapeshellarg($doc_root) . " && ";
        $rel_targets = array_map(function($p) use ($doc_root) { return ltrim(substr($p, strlen($doc_root)), '/'); }, $valid_targets);
        exec($cd_cmd . "zip -q -r " . escapeshellarg($tmp_zip) . " " . implode(' ', array_map('escapeshellarg', $rel_targets)));
        
        if (file_exists($tmp_zip)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="stackrium_download_'.date('Y-m-d').'.zip"');
            header('Content-Length: ' . filesize($tmp_zip));
            ob_clean();
            flush();
            readfile($tmp_zip);
            unlink($tmp_zip);
            exit;
        } else {
            $msg = "Error creating bulk download."; $msg_type = "error";
        }
    }
}

if (isset($_FILES['upload_file'])) {
    $target_file = $current_dir . '/' . basename($_FILES["upload_file"]["name"]);
    if (move_uploaded_file($_FILES["upload_file"]["tmp_name"], $target_file)) {
        $msg = "File uploaded."; $msg_type = "success";
    }
}

$view = $_GET['view'] ?? 'list';
$edit_target = $_GET['edit'] ?? '';
$edit_abs = '';
if ($view === 'edit' && $edit_target) {
    $test_path = realpath($doc_root . '/' . $edit_target);
    if ($test_path && strpos($test_path, $doc_root) === 0 && is_file($test_path)) {
        $edit_abs = $test_path;
    } else {
        $view = 'list';
    }
}

$show_hidden = isset($_COOKIE['show_hidden']) ? filter_var($_COOKIE['show_hidden'], FILTER_VALIDATE_BOOLEAN) : true;
if (isset($_GET['toggle_hidden'])) {
    $show_hidden = !$show_hidden;
    setcookie('show_hidden', $show_hidden ? 'true' : 'false', time() + (86400 * 30), "/");
    header("Location: ?p=" . urlencode($rel_path));
    exit;
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// URL builder helper for maintaining state during sorting/pagination
function buildUrl($params_to_update) {
    $params = $_GET;
    foreach ($params_to_update as $k => $v) { $params[$k] = $v; }
    return '?' . http_build_query($params);
}
function sortIcon($col, $sort_by, $sort_order) {
    if ($sort_by === $col) {
        return $sort_order === 'asc' ? ' <span class="text-indigo-400 font-bold ml-1">&uarr;</span>' : ' <span class="text-indigo-400 font-bold ml-1">&darr;</span>';
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackrium FM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        html.light-mode body { background-color: #f3f4f6 !important; color: #111827 !important; }
        html.light-mode .bg-\[\#0d1117\] { background-color: #ffffff !important; }
        html.light-mode .bg-gray-900 { background-color: #ffffff !important; border-color: #e5e7eb !important; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        html.light-mode .bg-gray-800 { background-color: #f3f4f6 !important; border-color: #d1d5db !important; color: #374151 !important; }
        html.light-mode .hover\:bg-gray-800:hover, html.light-mode .hover\:bg-gray-700:hover, html.light-mode .hover\:bg-gray-800\/30:hover { background-color: #e5e7eb !important; color: #111827 !important; }
        html.light-mode .text-gray-200, html.light-mode .text-gray-300, html.light-mode .text-white { color: #111827 !important; }
        html.light-mode .text-gray-400, html.light-mode .text-gray-500 { color: #4b5563 !important; }
        html.light-mode .border-gray-800, html.light-mode .border-gray-700 { border-color: #e5e7eb !important; }
        html.light-mode .divide-gray-800\/50 > :not([hidden]) ~ :not([hidden]) { border-color: #e5e7eb !important; }
    </style>
    <script>
        if (localStorage.getItem('theme') === 'light') document.documentElement.classList.add('light-mode');
    </script>
</head>
<body class="bg-[#0d1117] text-gray-300 min-h-screen font-sans antialiased pb-20">
<nav class="bg-gray-900 border-b border-gray-800 px-4 py-3 flex justify-between items-center shadow-lg relative z-10">
    <div class="flex items-center gap-3">
        <div class="h-8 w-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-500/20">S</div>
        <span class="text-lg font-semibold text-white hidden sm:block">Stackrium <span class="text-gray-500 text-sm font-normal ml-1"><?php echo $domain; ?></span></span>
    </div>
    <div class="flex gap-2">
        <a href="<?php echo buildUrl(['toggle_hidden'=>1]); ?>" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm rounded transition-all border border-gray-700 whitespace-nowrap"><?php echo $show_hidden ? 'Hide' : 'Show'; ?> .dotfiles</a>
        <button type="button" onclick="toggleTheme()" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm rounded text-gray-300 border border-gray-700">Theme</button>
        <a href="?p=" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm font-medium rounded transition-all shadow border border-gray-700 whitespace-nowrap">Root</a>
    </div>
</nav>

<?php
$items = scandir($current_dir);
$items_meta = [];
$stat_folders = 0; $stat_files = 0; $stat_size = 0;

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $abs_path = $current_dir . '/' . $item;
    $is_dir = is_dir($abs_path);
    
    if ($is_dir) {
        $stat_folders++;
    } else {
        $stat_files++;
        $stat_size += filesize($abs_path);
    }

    if (!$show_hidden && strpos($item, '.') === 0) continue;
    
    $items_meta[] = [
        'name' => $item,
        'is_dir' => $is_dir,
        'size' => $is_dir ? 0 : filesize($abs_path),
        'date' => filemtime($abs_path),
        'perms' => substr(sprintf('%o', fileperms($abs_path)), -4),
        'ext' => strtolower(pathinfo($abs_path, PATHINFO_EXTENSION))
    ];
}

// --- SORTING ENGINE ---
$sort_by = $_GET['sort'] ?? 'name';
$sort_order = $_GET['order'] ?? 'asc';

usort($items_meta, function($a, $b) use ($sort_by, $sort_order) {
    // Rule 1: Folders always float to the top
    if ($a['is_dir'] !== $b['is_dir']) {
        return $a['is_dir'] ? -1 : 1;
    }
    // Rule 2: Sort by selected column
    $res = 0;
    if ($sort_by === 'name') {
        $res = strnatcasecmp($a['name'], $b['name']);
    } elseif ($sort_by === 'size') {
        $res = $a['size'] <=> $b['size'];
    } elseif ($sort_by === 'date') {
        $res = $a['date'] <=> $b['date'];
    }
    return $sort_order === 'asc' ? $res : -$res;
});

// --- PAGINATION LOGIC ---
$total_items = count($items_meta);
$items_per_page = 50;
$total_pages = max(1, ceil($total_items / $items_per_page));
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
if ($current_page > $total_pages) $current_page = $total_pages;
$current_items = array_slice($items_meta, ($current_page - 1) * $items_per_page, $items_per_page);
?>

<!-- DIRECTORY STATS BAR -->
<div class="bg-gray-800/50 text-xs text-gray-400 px-4 sm:px-6 py-2 border-b border-gray-800 flex flex-wrap gap-4 sm:gap-6 shadow-inner mb-4">
    <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg> <?php echo $stat_folders; ?> Folders</span>
    <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg> <?php echo $stat_files; ?> Files</span>
    <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg> <?php echo formatBytes($stat_size); ?> Total Size</span>
</div>

<div class="max-w-7xl mx-auto p-4 sm:px-6 pb-20">
    <?php if ($msg): ?>
    <div class="mb-4 px-4 py-3 rounded border <?php echo $msg_type === 'error' ? 'bg-red-900/50 border-red-500/50 text-red-200' : 'bg-green-900/50 border-green-500/50 text-green-200'; ?>">
        <?php echo $msg; ?>
    </div>
    <?php endif; ?>

    <?php if ($view === 'edit'): ?>
        <!-- EDITOR VIEW -->
        <div class="flex justify-between items-center mb-4">
            <div class="text-sm text-gray-400 truncate max-w-[60%]">Editing: <span class="text-white font-mono"><?php echo htmlspecialchars($edit_target); ?></span></div>
            <div class="flex gap-2">
                <a href="?p=<?php echo urlencode($rel_path); ?>" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-sm rounded border border-gray-700">Close</a>
                <button onclick="document.getElementById('saveForm').submit();" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded shadow">Save</button>
            </div>
        </div>
        <form id="saveForm" method="POST" action="<?php echo buildUrl([]); ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="target" value="<?php echo htmlspecialchars($edit_target); ?>">
            <textarea name="content" spellcheck="false" class="w-full h-[70vh] bg-[#0d1117] text-gray-300 font-mono text-sm p-4 rounded-xl border border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none resize-none leading-relaxed"><?php echo htmlspecialchars(file_get_contents($edit_abs)); ?></textarea>
        </form>
    <?php else: ?>
        <!-- LIST VIEW -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
            <div class="flex items-center text-sm text-gray-400 font-mono bg-gray-900 px-4 py-2 rounded-lg border border-gray-800 w-full md:w-auto overflow-x-auto hide-scrollbar whitespace-nowrap">
                <a href="?p=" class="text-indigo-400 hover:text-indigo-300 mr-1 transition-colors">/public_html</a>
                <?php
                if ($rel_path) {
                    $parts = explode('/', $rel_path);
                    $build_path = '';
                    foreach ($parts as $index => $part) {
                        $build_path .= ($index === 0 ? '' : '/') . $part;
                        echo '<span class="text-gray-600 mx-1">/</span>';
                        if ($index === count($parts) - 1) {
                            echo '<span class="text-gray-200">' . htmlspecialchars($part) . '</span>';
                        } else {
                            echo '<a href="?p=' . urlencode($build_path) . '" class="text-indigo-400 hover:text-indigo-300 transition-colors">' . htmlspecialchars($part) . '</a>';
                        }
                    }
                }
                ?>
            </div>
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search files..." class="w-full md:w-48 px-3 py-1.5 bg-[#0d1117] border border-gray-800 rounded text-sm focus:border-indigo-500 outline-none text-gray-300 mb-2 md:mb-0">
                <div class="flex gap-2 w-full md:w-auto">
                    <button onclick="openModal('uploadModal')" class="flex-1 md:flex-none px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm rounded border border-gray-700">Upload</button>
                    <button onclick="openModal('newFolderModal')" class="flex-1 md:flex-none px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm rounded border border-gray-700">+Folder</button>
                    <button onclick="openModal('newFileModal')" class="flex-1 md:flex-none px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm rounded border border-gray-700">+File</button>
                </div>
            </div>
        </div>

        <div class="bg-gray-900 rounded-xl border border-gray-800 shadow-xl overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-800/50 text-gray-400 uppercase text-xs tracking-wider border-b border-gray-800">
                    <tr>
                        <th class="px-4 py-4 w-10"><input type="checkbox" onclick="toggleAll(this)" class="accent-indigo-500 w-4 h-4 cursor-pointer rounded bg-gray-900 border-gray-700"></th>
                        <th class="px-2 md:px-6 py-4 font-medium"><a href="<?php echo buildUrl(['sort'=>'name', 'order'=>($sort_by==='name'&&$sort_order==='asc'?'desc':'asc')]); ?>" class="hover:text-white transition-colors flex items-center">Name<?php echo sortIcon('name', $sort_by, $sort_order); ?></a></th>
                        <th class="px-4 md:px-6 py-4 font-medium hidden sm:table-cell w-24"><a href="<?php echo buildUrl(['sort'=>'size', 'order'=>($sort_by==='size'&&$sort_order==='asc'?'desc':'asc')]); ?>" class="hover:text-white transition-colors flex items-center">Size<?php echo sortIcon('size', $sort_by, $sort_order); ?></a></th>
                        <th class="px-4 md:px-6 py-4 font-medium hidden md:table-cell w-40"><a href="<?php echo buildUrl(['sort'=>'date', 'order'=>($sort_by==='date'&&$sort_order==='asc'?'desc':'asc')]); ?>" class="hover:text-white transition-colors flex items-center">Modified<?php echo sortIcon('date', $sort_by, $sort_order); ?></a></th>
                        <th class="px-4 md:px-6 py-4 font-medium hidden lg:table-cell w-24">Perms</th>
                        <th class="px-4 md:px-6 py-4 font-medium text-right w-48">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50" id="fileTableBody">
                    <?php if ($rel_path): ?>
                    <tr class="hover:bg-gray-800/30">
                        <td colspan="6" class="px-4 md:px-6 py-3">
                            <a href="?p=<?php echo urlencode($parent_dir); ?>" class="flex items-center gap-2 text-indigo-400 hover:text-indigo-300 font-medium">← Go Up</a>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($current_items as $item_meta):
                        $item = $item_meta['name'];
                        $is_dir = $item_meta['is_dir'];
                        $item_rel = ($rel_path ? $rel_path . '/' : '') . $item;
                        $size = $is_dir ? '-' : formatBytes($item_meta['size']);
                        $date = date("M d, Y H:i", $item_meta['date']);
                        $perms = $item_meta['perms'];
                        $ext = $item_meta['ext'];
                        $is_img = in_array($ext, ['jpg','jpeg','png','gif','svg','webp','bmp','ico']);
                        $is_doc = in_array($ext, ['pdf','mp3','mp4','webm','ogg','wav']);
                        $is_archive = in_array($ext, ['zip','tar','gz','rar','7z','bz2']);
                        $id_hash = md5($item_rel);
                    ?>
                    <tr class="hover:bg-gray-800/30 group file-row" data-name="<?php echo htmlspecialchars(strtolower($item)); ?>">
                        <td class="px-4 py-3 w-10">
                            <input type="checkbox" value="<?php echo htmlspecialchars($item_rel); ?>" class="file-check accent-indigo-500 w-4 h-4 cursor-pointer rounded" onclick="checkBulk()">
                        </td>
                        <td class="px-2 md:px-6 py-3 truncate max-w-[150px] sm:max-w-xs md:max-w-md">
                            <div class="flex items-center gap-2">
                                <?php if ($is_dir): ?>
                                    <svg class="w-5 h-5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
                                    <a href="?p=<?php echo urlencode($item_rel); ?>" class="text-gray-200 hover:text-white font-medium truncate block"><?php echo htmlspecialchars($item); ?></a>
                                <?php else: ?>
                                    <svg class="w-5 h-5 text-gray-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                    <?php if ($is_img || $is_doc): ?>
                                        <a href="?raw=<?php echo urlencode($item_rel); ?>" target="_blank" class="text-gray-300 hover:text-indigo-400 truncate block text-left"><?php echo htmlspecialchars($item); ?></a>
                                    <?php elseif ($is_archive): ?>
                                        <a href="?dl=<?php echo urlencode($item_rel); ?>" class="text-gray-300 hover:text-indigo-400 truncate block text-left"><?php echo htmlspecialchars($item); ?></a>
                                    <?php else: ?>
                                        <a href="?view=edit&edit=<?php echo urlencode($item_rel); ?>&p=<?php echo urlencode($rel_path); ?>" class="text-gray-300 hover:text-indigo-400 truncate block"><?php echo htmlspecialchars($item); ?></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 md:px-6 py-3 text-gray-500 text-sm hidden sm:table-cell"><?php echo $size; ?></td>
                        <td class="px-4 md:px-6 py-3 text-gray-500 text-sm hidden md:table-cell"><?php echo $date; ?></td>
                        <td class="px-4 md:px-6 py-3 text-gray-500 text-sm hidden lg:table-cell font-mono">
                            <button onclick="pAct('chmodModal', '<?php echo htmlspecialchars($item_rel); ?>', '<?php echo $perms; ?>')" class="hover:text-white"><?php echo $perms; ?></button>
                        </td>
                        <td class="px-4 md:px-6 py-3 text-right">
                            <div class="md:hidden relative inline-block text-left">
                                <button onclick="toggleMenu('m-<?php echo $id_hash; ?>')" class="text-gray-400 hover:text-white px-2 py-1">⋮</button>
                                <div id="m-<?php echo $id_hash; ?>" class="hidden absolute right-0 z-10 mt-1 w-32 origin-top-right rounded-md bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none overflow-hidden">
                                    <?php if (!$is_dir): ?><a href="?dl=<?php echo urlencode($item_rel); ?>" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Download</a><?php endif; ?>
                                    <button onclick="pAct('renameModal', '<?php echo htmlspecialchars($item_rel); ?>', '<?php echo htmlspecialchars($item); ?>')" class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Rename</button>
                                    <button onclick="pAct('copyModal', '<?php echo htmlspecialchars($item_rel); ?>', '<?php echo htmlspecialchars($item_rel . ($is_dir ? '-copy' : '.copy')); ?>')" class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Copy</button>
                                    <button onclick="pAct('moveModal', '<?php echo htmlspecialchars($item_rel); ?>', '<?php echo htmlspecialchars($item_rel); ?>')" class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Move</button>
                                    <?php if ($ext === 'zip'): ?>
                                    <form method="POST" class="block w-full">
                                        <input type="hidden" name="action" value="extract"><input type="hidden" name="target" value="<?php echo htmlspecialchars($item_rel); ?>">
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-yellow-400 hover:bg-gray-700">Extract</button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" onsubmit="return confirm('Delete?');" class="block w-full">
                                        <input type="hidden" name="action" value="delete"><input type="hidden" name="target" value="<?php echo htmlspecialchars($item_rel); ?>">
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-gray-700">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <div class="hidden md:flex justify-end gap-3 text-gray-500">
                                <?php if (!$is_dir): ?>
                                    <a href="?dl=<?php echo urlencode($item_rel); ?>" title="Download" class="hover:text-green-400 transition-colors">
                                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    </a>
                                <?php endif; ?>
                                <button onclick="pAct('renameModal', '<?php echo htmlspecialchars($item_rel); ?>', '<?php echo htmlspecialchars($item); ?>')" title="Rename" class="hover:text-blue-400 transition-colors">
                                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button onclick="pAct('copyModal', '<?php echo htmlspecialchars($item_rel); ?>', '<?php echo htmlspecialchars($item_rel . ($is_dir ? '-copy' : '.copy')); ?>')" title="Copy" class="hover:text-gray-200 transition-colors">
                                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </button>
                                <button onclick="pAct('moveModal', '<?php echo htmlspecialchars($item_rel); ?>', '<?php echo htmlspecialchars($item_rel); ?>')" title="Move" class="hover:text-gray-200 transition-colors">
                                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                <?php if ($ext === 'zip'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="extract"><input type="hidden" name="target" value="<?php echo htmlspecialchars($item_rel); ?>">
                                    <button type="submit" title="Extract Here" class="hover:text-yellow-400 transition-colors">
                                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this?');">
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="target" value="<?php echo htmlspecialchars($item_rel); ?>">
                                    <button type="submit" title="Delete" class="hover:text-red-400 transition-colors">
                                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION CONTROLS -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-between items-center mt-4 bg-gray-900 px-4 py-3 rounded-xl border border-gray-800 shadow-sm">
            <div class="text-sm text-gray-400">
                Showing <span class="text-gray-200"><?php echo (($current_page - 1) * $items_per_page) + 1; ?></span> to <span class="text-gray-200"><?php echo min($current_page * $items_per_page, $total_items); ?></span> of <span class="text-gray-200"><?php echo $total_items; ?></span> items
            </div>
            <div class="flex gap-1 overflow-x-auto hide-scrollbar">
                <?php
                if ($current_page > 1) {
                    echo '<a href="'.buildUrl(['page'=>1]).'" class="px-3 py-1 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded border border-gray-700 text-sm transition-colors font-medium">First</a>';
                    echo '<a href="'.buildUrl(['page'=>($current_page - 1)]).'" class="px-3 py-1 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded border border-gray-700 text-sm transition-colors">Prev</a>';
                }

                $start_p = max(1, $current_page - 2);
                $end_p = min($total_pages, $current_page + 2);
                for ($i = $start_p; $i <= $end_p; $i++) {
                    $active = $i === $current_page ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-gray-800 hover:bg-gray-700 text-gray-300 border-gray-700';
                    echo '<a href="'.buildUrl(['page'=>$i]).'" class="px-3 py-1 rounded border text-sm transition-colors '.$active.'">'.$i.'</a>';
                }

                if ($current_page < $total_pages) {
                    echo '<a href="'.buildUrl(['page'=>($current_page + 1)]).'" class="px-3 py-1 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded border border-gray-700 text-sm transition-colors">Next</a>';
                    echo '<a href="'.buildUrl(['page'=>$total_pages]).'" class="px-3 py-1 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded border border-gray-700 text-sm transition-colors font-medium">Last</a>';
                }
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- HIDDEN FORM FOR BULK ACTIONS -->
        <form id="bulkForm" method="POST" action="<?php echo buildUrl([]); ?>">
            <input type="hidden" name="action" id="bulkAction" value="">
            <div id="bulkTargetsContainer"></div>
        </form>

        <!-- FLOATING BULK TOOLBAR -->
        <div id="bulkToolbar" class="hidden fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-gray-900 border border-gray-700 shadow-2xl rounded-full px-6 py-3 flex items-center gap-4 z-50">
            <span id="bulkCount" class="text-indigo-400 font-bold text-sm">0 selected</span>
            <div class="h-5 w-px bg-gray-700"></div>
            <button type="button" onclick="submitBulk('delete')" class="text-sm text-red-400 hover:text-red-300 font-medium transition-colors">Delete</button>
            <button type="button" onclick="openDestModal('copy')" class="text-sm text-blue-400 hover:text-blue-300 font-medium transition-colors">Copy</button>
            <button type="button" onclick="openDestModal('move')" class="text-sm text-blue-400 hover:text-blue-300 font-medium transition-colors">Move</button>
            <button type="button" onclick="openZipModal()" class="text-sm text-yellow-400 hover:text-yellow-300 font-medium transition-colors">Zip</button>
            <button type="button" onclick="submitBulk('bulk_download')" class="text-sm text-purple-400 hover:text-purple-300 font-medium transition-colors">Download</button>
        </div>

        <!-- MODALS -->
        <?php
        function rModal($id, $title,$action, $inp,$plh) {
            echo "<div id='$id' class='hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50'><div class='bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-sm shadow-2xl'><h3 class='text-lg font-medium text-white mb-4'>$title</h3><form method='POST'><input type='hidden' name='action' value='$action'><input type='hidden' name='target' id='{$id}T'><input type='text' name='$inp' id='{$id}I' placeholder='$plh' required class='w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-sm mb-4 outline-none focus:border-indigo-500 font-mono'><div class='flex justify-end gap-2'><button type='button' onclick='closeModal(\"$id\")' class='px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded text-sm text-gray-300'>Cancel</button><button type='submit' class='px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm text-white font-medium shadow'>Confirm</button></div></form></div></div>";
        }
        rModal('newFolderModal', 'New Folder', 'mkdir', 'new_name', 'Folder Name');
        rModal('newFileModal', 'New File', 'mkfile', 'new_name', 'filename.php');
        rModal('renameModal', 'Rename', 'rename', 'new_name', 'New Name');
        rModal('chmodModal', 'Chmod Permissions', 'chmod', 'perms', '0644');
        ?>

        <!-- UNIFIED DESTINATION MODAL (Move/Copy) -->
        <div id="destModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-lg shadow-2xl">
                <h3 id="destModalTitle" class="text-lg font-medium text-white mb-2">Move Items</h3>
                <p class="text-sm text-gray-400 mb-4 leading-relaxed">
                    Type the destination path below, or navigate using the Quick Select cards.
                </p>
                
                <form id="destForm" method="POST" action="<?php echo buildUrl([]); ?>">
                    <input type="hidden" name="action" id="destAction" value="">
                    <div id="destTargetsContainer"></div>
                    
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-gray-500 font-mono text-sm whitespace-nowrap">Path:</span>
                        <input type="text" name="dest" id="destInput" placeholder="e.g. / or assets/js/" required class="w-full bg-[#0d1117] border border-gray-800 rounded px-3 py-2 text-white text-sm outline-none focus:border-indigo-500 font-mono">
                    </div>

                    <!-- QUICK FOLDER SELECTOR -->
                    <div class="mb-6">
                        <label class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-2 block">Quick Navigate</label>
                        <div id="quickSelectCards" class="max-h-40 overflow-y-auto hide-scrollbar bg-gray-950 border border-gray-800 rounded-lg p-3 flex flex-wrap gap-2 shadow-inner">
                            <!-- Cards load here dynamically via AJAX -->
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-2 mt-2">
                        <button type="button" onclick="closeModal('destModal')" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded text-sm text-gray-300 transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm text-white font-medium shadow-lg shadow-indigo-500/20 transition-colors">Confirm</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ZIP MODAL -->
        <div id="zipModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-sm shadow-2xl">
                <h3 class="text-lg font-medium text-white mb-4">Compress to ZIP</h3>
                <form id="zipForm" method="POST" action="<?php echo buildUrl([]); ?>">
                    <input type="hidden" name="action" value="zip">
                    <div id="zipTargetsContainer"></div>
                    <input type="text" name="new_name" placeholder="archive.zip" value="archive.zip" required class="w-full bg-[#0d1117] border border-gray-800 rounded px-3 py-2 text-white text-sm mb-4 outline-none focus:border-indigo-500 font-mono">
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeModal('zipModal')" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded text-sm text-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm text-white font-medium shadow-lg shadow-indigo-500/20">Compress</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="uploadModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50"><div class="bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-sm shadow-2xl"><h3 class="text-lg font-medium text-white mb-4">Upload File</h3><form method="POST" enctype="multipart/form-data"><input type="file" name="upload_file" required class="w-full text-gray-400 text-sm mb-4 border border-gray-800 p-2 rounded bg-gray-950"><div class="flex justify-end gap-2"><button type="button" onclick="closeModal('uploadModal')" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded text-sm text-gray-300">Cancel</button><button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm text-white font-medium shadow">Upload</button></div></form></div></div>

        <!-- IMAGE PREVIEW -->
        <div id="previewModal" class="hidden fixed inset-0 bg-black/90 flex items-center justify-center p-4 z-50" onclick="closeModal('previewModal')">
            <div class="relative bg-gray-900 p-2 rounded-lg border border-gray-800" onclick="event.stopPropagation()">
                <button onclick="closeModal('previewModal')" class="absolute -top-4 -right-4 bg-red-600 text-white w-8 h-8 rounded-full shadow-lg font-bold">X</button>
                <img id="pImgSrc" class="max-w-full max-h-[85vh] rounded">
            </div>
        </div>

        <script>
            function toggleTheme() {
                document.documentElement.classList.toggle('light-mode');
                localStorage.setItem('theme', document.documentElement.classList.contains('light-mode') ? 'light' : 'dark');
            }
            function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
            function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
            
            function pAct(id, target, val='') {
                if (id === 'copyModal') { openDestModal('copy', target); return; }
                if (id === 'moveModal') { openDestModal('move', target); return; }
                
                let t = document.getElementById(id+'T'), i = document.getElementById(id+'I');
                if(t) t.value = target; if(i) i.value = val;
                openModal(id);
                document.querySelectorAll('[id^="m-"]').forEach(m => m.classList.add('hidden'));
            }
            
            function previewImg(url) { document.getElementById('pImgSrc').src = url; openModal('previewModal'); }
            function filterTable() {
                let v = document.getElementById('searchInput').value.toLowerCase();
                document.querySelectorAll('.file-row').forEach(r => {
                    r.style.display = r.getAttribute('data-name').includes(v) ? '' : 'none';
                });
            }
            function toggleMenu(id) {
                document.querySelectorAll('[id^="m-"]').forEach(m => { if(m.id !== id) m.classList.add('hidden'); });
                document.getElementById(id).classList.toggle('hidden');
            }
            document.addEventListener('click', e => {
                if(!e.target.closest('.relative')) document.querySelectorAll('[id^="m-"]').forEach(m => m.classList.add('hidden'));
            });

            function toggleAll(source) {
                document.querySelectorAll('.file-check').forEach(cb => cb.checked = source.checked);
                checkBulk();
            }
            function checkBulk() {
                let checked = document.querySelectorAll('.file-check:checked');
                let tb = document.getElementById('bulkToolbar');
                let count = document.getElementById('bulkCount');
                if(checked.length > 0) {
                    count.innerText = checked.length + ' selected';
                    tb.classList.remove('hidden');
                } else {
                    tb.classList.add('hidden');
                }
            }
            
            async function navigateDestPath(folderAction) {
                let input = document.getElementById('destInput');
                let currentVal = input.value;
                
                if (folderAction !== '') {
                    let newPath = currentVal;
                    if (folderAction === '/') {
                        newPath = '/';
                    } else if (folderAction === '../') {
                        let parts = newPath.split('/').filter(p => p !== '');
                        parts.pop();
                        newPath = parts.length > 0 ? parts.join('/') + '/' : '/';
                    } else {
                        if (newPath && !newPath.endsWith('/')) newPath += '/';
                        if (newPath === '/') newPath = '';
                        newPath += folderAction + '/';
                    }
                    input.value = newPath;
                }
                
                try {
                    let fd = new FormData();
                    fd.append('ajax_get_folders', input.value);
                    let res = await fetch(window.location.href, { method: 'POST', body: fd });
                    let folders = await res.json();
                    
                    let container = document.getElementById('quickSelectCards');
                    let html = `
                        <button type="button" onclick="navigateDestPath('/')" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-900 hover:bg-gray-800 border border-gray-700 rounded-md text-sm text-indigo-400 transition-colors shadow-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>/ (Root)</button>
                        <button type="button" onclick="navigateDestPath('../')" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-900 hover:bg-gray-800 border border-gray-700 rounded-md text-sm text-gray-300 transition-colors shadow-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>../ (Up)</button>
                    `;
                    
                    folders.forEach(f => {
                        html += `<button type="button" onclick="navigateDestPath('${f}')" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-900 hover:bg-gray-800 border border-gray-700 rounded-md text-sm text-gray-300 transition-colors shadow-sm"><svg class="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>${f}</button>`;
                    });
                    container.innerHTML = html;
                } catch (e) {
                    console.error("AJAX Error:", e);
                }
            }

            function openDestModal(action, singleTarget = null) {
                document.querySelectorAll('[id^="m-"]').forEach(m => m.classList.add('hidden')); 
                document.getElementById('destModalTitle').innerText = action === 'copy' ? 'Copy Items' : 'Move Items';
                document.getElementById('destAction').value = action;
                
                let container = document.getElementById('destTargetsContainer');
                container.innerHTML = '';
                
                if (singleTarget) {
                    let input = document.createElement('input'); input.type = 'hidden'; input.name = 'targets[]'; input.value = singleTarget;
                    container.appendChild(input);
                } else {
                    document.querySelectorAll('.file-check:checked').forEach(cb => {
                        let input = document.createElement('input'); input.type = 'hidden'; input.name = 'targets[]'; input.value = cb.value;
                        container.appendChild(input);
                    });
                }
                
                let currentPath = '<?php echo $rel_path ? addslashes(htmlspecialchars($rel_path)) . "/" : ""; ?>';
                document.getElementById('destInput').value = currentPath || '/';
                navigateDestPath('');
                openModal('destModal');
            }

            function openZipModal() {
                let container = document.getElementById('zipTargetsContainer');
                container.innerHTML = '';
                document.querySelectorAll('.file-check:checked').forEach(cb => {
                    let input = document.createElement('input'); input.type = 'hidden'; input.name = 'targets[]'; input.value = cb.value;
                    container.appendChild(input);
                });
                openModal('zipModal');
            }

            function submitBulk(action) {
                if (action === 'delete' && !confirm('Are you sure you want to delete the selected items?')) return;
                
                document.getElementById('bulkAction').value = action;
                
                let container = document.getElementById('bulkTargetsContainer');
                container.innerHTML = '';
                document.querySelectorAll('.file-check:checked').forEach(cb => {
                    let input = document.createElement('input'); input.type = 'hidden'; input.name = 'targets[]'; input.value = cb.value;
                    container.appendChild(input);
                });
                document.getElementById('bulkForm').submit();
            }
        </script>
    <?php endif; ?>
</div>
</body>
</html>
EOF

# 4. STRICT OWNERSHIP
chown -R $USERNAME:$USERNAME "$FM_DIR"
chmod 644 "$FM_DIR/index.php"
chmod 644 "$FM_DIR/config.php"

# 5. SAFE NGINX CONFIGURATION
if ! grep -q "location \^~ /filemanager" "$VHOST"; then
    cp "$VHOST" "${VHOST}.bak"
    awk -v web_root="$WEB_ROOT" -v php_ver="$PHP_VER" -v user="$USERNAME" '/location \/ \{/ {
        print "    # Stackrium Native File Manager"
        print "    location ^~ /filemanager {"
        print "        modsecurity off;"
        print "        root " web_root ";"
        print "        index index.php;"
        print "        location ~ \\.php$ {"
        print "            modsecurity off;"
        print "            include snippets/fastcgi-php.conf;"
        print "            fastcgi_pass unix:/run/php/php" php_ver "-fpm-" user ".sock;"
        print "        }"
        print "    }"
        print ""
    } 1' "$VHOST" > "${VHOST}.tmp" && mv "${VHOST}.tmp" "$VHOST"
fi

# 6. TEST AND RELOAD
if nginx -t > /dev/null 2>&1; then
    /opt/panel/scripts/nginx_reload_callback.sh "$TASK_ID" > /dev/null 2>&1 &
    rm -f "${VHOST}.bak"
    echo "Success: Stackrium Native File Manager deployed for $DOMAIN."
    exit 0
else
    mv "${VHOST}.bak" "$VHOST" 2>/dev/null
    echo "Error: Nginx syntax failed. Rolled back safely."
    exit 1
fi