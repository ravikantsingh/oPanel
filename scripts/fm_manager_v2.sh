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
?>
EOF

# 3. GENERATE STACKRIUM NATIVE FILE MANAGER
cat << 'EOF' > "$FM_DIR/index.php"
<?php
require 'config.php';

// Force local sessions to completely bypass global server permission blocks
session_set_cookie_params(['path' => '/', 'domain' => '', 'samesite' => 'Lax']);
session_name('filemanager');
session_start();

// --- SSO SECURITY VERIFICATION ---
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
// ---------------------------------

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

// --- DOWNLOAD FEATURE ---
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
        readfile($dl_path);
        exit;
    }
}

// --- RAW PREVIEW FEATURE (Images) ---
if (isset($_GET['raw'])) {
    $raw_path = realpath($doc_root . '/' . ltrim($_GET['raw'], '/'));
    if ($raw_path && strpos($raw_path, $doc_root) === 0 && is_file($raw_path)) {
        $mime = mime_content_type($raw_path);
        header("Content-Type: $mime");
        readfile($raw_path);
        exit;
    }
}

$msg = '';
$msg_type = '';

// --- ACTIONS (Save, Upload, Mkdir, Mkfile, Delete, Rename, Copy, Move, Chmod) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $target = isset($_POST['target']) ? realpath($doc_root . '/' . ltrim($_POST['target'], '/')) : false;
    
    // Ensure target is within doc_root
    if ($target && strpos($target, $doc_root) !== 0) $target = false;

    if ($action === 'save' && $target && is_file($target)) {
        if (file_put_contents($target, $_POST['content'] ?? '') !== false) {
            $msg = "File saved successfully."; $msg_type = "success";
        } else { $msg = "Error saving file."; $msg_type = "error"; }
    }
    
    if ($action === 'delete' && $target && $target !== $doc_root) {
        is_dir($target) ? exec("rm -rf " . escapeshellarg($target)) : unlink($target);
        $msg = "Item deleted."; $msg_type = "success";
    }

    if ($action === 'mkdir') {
        $new = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['new_name']);
        if ($new && mkdir($current_dir . '/' . $new)) { $msg = "Folder created."; $msg_type = "success"; }
    }

    if ($action === 'mkfile') {
        $new = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['new_name']);
        if ($new && touch($current_dir . '/' . $new)) { $msg = "File created."; $msg_type = "success"; }
    }
    
    if ($action === 'rename' && $target) {
        $new_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['new_name']);
        if ($new_name && rename($target, dirname($target) . '/' . $new_name)) {
            $msg = "Renamed successfully."; $msg_type = "success";
        }
    }

    if ($action === 'copy' && $target) {
        $dest = $doc_root . '/' . ltrim($_POST['dest'], '/');
        if (strpos(realpath(dirname($dest)), $doc_root) === 0) {
            is_dir($target) ? exec("cp -r " . escapeshellarg($target) . " " . escapeshellarg($dest)) : copy($target, $dest);
            $msg = "Copied successfully."; $msg_type = "success";
        }
    }

    if ($action === 'move' && $target && $target !== $doc_root) {
        $dest = $doc_root . '/' . ltrim($_POST['dest'], '/');
        if (strpos(realpath(dirname($dest)), $doc_root) === 0) {
            rename($target, $dest);
            $msg = "Moved successfully."; $msg_type = "success";
        }
    }

    if ($action === 'chmod' && $target) {
        $perms = octdec($_POST['perms']);
        if ($perms && chmod($target, $perms)) {
            $msg = "Permissions updated."; $msg_type = "success";
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

// Toggle Hidden files
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
        
        /* Light Mode Overrides */
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
<body class="bg-[#0d1117] text-gray-300 min-h-screen font-sans antialiased">
<nav class="bg-gray-900 border-b border-gray-800 px-4 py-3 flex justify-between items-center shadow-lg">
    <div class="flex items-center gap-3">
        <div class="h-8 w-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-500/20">S</div>
        <span class="text-lg font-semibold text-white hidden sm:block">Stackrium <span class="text-gray-500 text-sm font-normal ml-1"><?php echo $domain; ?></span></span>
    </div>
    <div class="flex gap-2">
        <a href="?toggle_hidden=1&p=<?php echo urlencode($rel_path); ?>" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm rounded transition-all border border-gray-700 whitespace-nowrap"><?php echo $show_hidden ? 'Hide' : 'Show'; ?> .dotfiles</a>
        <button type="button" onclick="toggleTheme()" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm rounded text-gray-300 border border-gray-700">Theme</button>
        <a href="?p=" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm font-medium rounded transition-all shadow border border-gray-700 whitespace-nowrap">Root</a>
    </div>
</nav>

<div class="max-w-7xl mx-auto p-4 sm:p-6">
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
                <a href="?p=<?php echo urlencode($rel_path); ?>" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-sm rounded border border-gray-700">Cancel</a>
                <button onclick="document.getElementById('saveForm').submit();" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded shadow">Save</button>
            </div>
        </div>
        <form id="saveForm" method="POST" action="?p=<?php echo urlencode($rel_path); ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="target" value="<?php echo htmlspecialchars($edit_target); ?>">
            <textarea name="content" spellcheck="false" class="w-full h-[70vh] bg-[#0d1117] text-gray-300 font-mono text-sm p-4 rounded-xl border border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none resize-none leading-relaxed"><?php echo htmlspecialchars(file_get_contents($edit_abs)); ?></textarea>
        </form>
    <?php else: ?>
        <!-- LIST VIEW -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
            <div class="flex items-center text-sm text-gray-400 font-mono bg-gray-900 px-4 py-2 rounded-lg border border-gray-800 w-full md:w-auto overflow-x-auto hide-scrollbar whitespace-nowrap">
                <span class="text-indigo-400 mr-1">/public_html</span>
                <?php if ($rel_path): ?>
                    <span class="text-gray-600 mr-1">/</span>
                    <span class="text-gray-200"><?php echo htmlspecialchars($rel_path); ?></span>
                <?php endif; ?>
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
                        <th class="px-4 md:px-6 py-4 font-medium">Name</th>
                        <th class="px-4 md:px-6 py-4 font-medium hidden sm:table-cell w-24">Size</th>
                        <th class="px-4 md:px-6 py-4 font-medium hidden md:table-cell w-24">Perms</th>
                        <th class="px-4 md:px-6 py-4 font-medium text-right w-48">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50" id="fileTableBody">
                    <?php if ($rel_path): ?>
                    <tr class="hover:bg-gray-800/30">
                        <td colspan="4" class="px-4 md:px-6 py-3">
                            <a href="?p=<?php echo urlencode($parent_dir); ?>" class="flex items-center gap-2 text-indigo-400 hover:text-indigo-300 font-medium">← Go Up</a>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php
                    $items = scandir($current_dir);
                    $folders = []; $files = [];
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') continue;
                        if (!$show_hidden && strpos($item, '.') === 0) continue;
                        $abs_path = $current_dir . '/' . $item;
                        if (is_dir($abs_path)) $folders[] = $item; else $files[] = $item;
                    }
                    natcasesort($folders);
                    natcasesort($files);

                    foreach (array_merge($folders, $files) as $item):
                        $abs_path = $current_dir . '/' . $item;
                        $is_dir = is_dir($abs_path);
                        $item_rel = ($rel_path ? $rel_path . '/' : '') . $item;
                        $size = $is_dir ? '-' : formatBytes(filesize($abs_path));
                        $perms = substr(sprintf('%o', fileperms($abs_path)), -4);
                        $ext = strtolower(pathinfo($abs_path, PATHINFO_EXTENSION));
                        $is_img = in_array($ext, ['jpg','jpeg','png','gif','svg','webp']);
                        $id_hash = md5($item_rel);
                    ?>
                    <tr class="hover:bg-gray-800/30 group file-row" data-name="<?php echo htmlspecialchars(strtolower($item)); ?>">
                        <td class="px-4 md:px-6 py-3 truncate max-w-[150px] sm:max-w-xs md:max-w-md">
                            <div class="flex items-center gap-2">
                                <?php if ($is_dir): ?>
                                    <svg class="w-5 h-5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
                                    <a href="?p=<?php echo urlencode($item_rel); ?>" class="text-gray-200 hover:text-white font-medium truncate block"><?php echo htmlspecialchars($item); ?></a>
                                <?php else: ?>
                                    <svg class="w-5 h-5 text-gray-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>
                                    <?php if ($is_img): ?>
                                        <button onclick="previewImg('?raw=<?php echo urlencode($item_rel); ?>')" class="text-gray-300 hover:text-indigo-400 truncate block text-left"><?php echo htmlspecialchars($item); ?></button>
                                    <?php else: ?>
                                        <a href="?view=edit&edit=<?php echo urlencode($item_rel); ?>&p=<?php echo urlencode($rel_path); ?>" class="text-gray-300 hover:text-indigo-400 truncate block"><?php echo htmlspecialchars($item); ?></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 md:px-6 py-3 text-gray-500 text-sm hidden sm:table-cell"><?php echo $size; ?></td>
                        <td class="px-4 md:px-6 py-3 text-gray-500 text-sm hidden md:table-cell font-mono">
                            <button onclick="pAct('chmodModal', '<?php echo htmlspecialchars($item_rel); ?>', '<?php echo $perms; ?>')" class="hover:text-white"><?php echo $perms; ?></button>
                        </td>
                        <td class="px-4 md:px-6 py-3 text-right">
                            <!-- Mobile Menu Trigger -->
                            <div class="md:hidden relative inline-block text-left">
                                <button onclick="toggleMenu('m-<?php echo $id_hash; ?>')" class="text-gray-400 hover:text-white px-2 py-1">⋮</button>
                                <div id="m-<?php echo $id_hash; ?>" class="hidden absolute right-0 z-10 mt-1 w-32 origin-top-right rounded-md bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none overflow-hidden">
                                    <?php if (!$is_dir): ?><a href="?dl=<?php echo urlencode($item_rel); ?>" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Download</a><?php endif; ?>
                                    <button onclick="pAct('renameModal', '<?php echo htmlspecialchars($item_rel); ?>', '<?php echo htmlspecialchars($item); ?>')" class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Rename</button>
                                    <button onclick="pAct('copyModal', '<?php echo htmlspecialchars($item_rel); ?>', '<?php echo htmlspecialchars($item_rel . ($is_dir ? '-copy' : '.copy')); ?>')" class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Copy</button>
                                    <button onclick="pAct('moveModal', '<?php echo htmlspecialchars($item_rel); ?>', '<?php echo htmlspecialchars($item_rel); ?>')" class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Move</button>
                                    <form method="POST" onsubmit="return confirm('Delete?');" class="block w-full">
                                        <input type="hidden" name="action" value="delete"><input type="hidden" name="target" value="<?php echo htmlspecialchars($item_rel); ?>">
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-gray-700">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <!-- Desktop Actions -->
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

        <!-- MODALS -->
        <?php
        function rModal($id, $title, $action, $inp, $plh) {
            echo "<div id='$id' class='hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50'><div class='bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-sm'><h3 class='text-lg font-medium text-white mb-4'>$title</h3><form method='POST'><input type='hidden' name='action' value='$action'><input type='hidden' name='target' id='{$id}T'><input type='text' name='$inp' id='{$id}I' placeholder='$plh' required class='w-full bg-[#0d1117] border border-gray-800 rounded px-3 py-2 text-white text-sm mb-4 outline-none focus:border-indigo-500'><div class='flex justify-end gap-2'><button type='button' onclick='closeModal(\"$id\")' class='px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded text-sm text-gray-300'>Cancel</button><button type='submit' class='px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm text-white font-medium'>Confirm</button></div></form></div></div>";
        }
        rModal('newFolderModal', 'New Folder', 'mkdir', 'new_name', 'Folder Name');
        rModal('newFileModal', 'New File', 'mkfile', 'new_name', 'filename.php');
        rModal('renameModal', 'Rename', 'rename', 'new_name', 'New Name');
        rModal('copyModal', 'Copy To', 'copy', 'dest', 'Path (e.g. dir/file.php)');
        rModal('moveModal', 'Move To', 'move', 'dest', 'Path (e.g. dir/file.php)');
        rModal('chmodModal', 'Chmod Permissions', 'chmod', 'perms', '0644');
        ?>

        <div id="uploadModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50"><div class="bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-sm"><h3 class="text-lg font-medium text-white mb-4">Upload File</h3><form method="POST" enctype="multipart/form-data"><input type="file" name="upload_file" required class="w-full text-gray-400 text-sm mb-4 border border-gray-800 p-2 rounded bg-[#0d1117]"><div class="flex justify-end gap-2"><button type="button" onclick="closeModal('uploadModal')" class="px-4 py-2 bg-gray-800 rounded text-sm">Cancel</button><button type="submit" class="px-4 py-2 bg-indigo-600 rounded text-sm text-white">Upload</button></div></form></div></div>

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
                let t = document.getElementById(id+'T'), i = document.getElementById(id+'I');
                if(t) t.value = target; if(i) i.value = val;
                openModal(id);
                document.querySelectorAll('[id^="m-"]').forEach(m => m.classList.add('hidden')); // close menus
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
            // Close menus on outside click
            document.addEventListener('click', e => {
                if(!e.target.closest('.relative')) document.querySelectorAll('[id^="m-"]').forEach(m => m.classList.add('hidden'));
            });
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