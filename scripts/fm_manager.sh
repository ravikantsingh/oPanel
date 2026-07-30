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
session_save_path(__DIR__ . '/sessions');
session_name('stackrium_fm');
session_start();

// --- SSO SECURITY VERIFICATION ---
if (isset($_GET['sso_t']) && isset($_GET['sso_h'])) {
    $expected = hash_hmac('sha256', $domain . '|' . $_GET['sso_t'], $secret);
    if (hash_equals($expected, $_GET['sso_h']) && (time() - $_GET['sso_t'] < 60)) {
        $_SESSION['logged_in'] = true;
        session_write_close();
        $clean_url = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: " . $clean_url);
        exit;
    } else {
        die("<div style='background:#111;color:red;padding:20px;'>Stackrium SSO Error: Signature mismatch. Please relaunch from panel.</div>");
    }
}

if (empty($_SESSION['logged_in'])) {
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

$msg = '';
$msg_type = '';

// --- ACTIONS (Save, Upload, Mkdir, Mkfile, Delete, Rename) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'save') {
        $target = $_POST['target'];
        $content = $_POST['content'] ?? '';
        $file_path = realpath($doc_root . '/' . $target);
        if ($file_path && strpos($file_path, $doc_root) === 0 && is_file($file_path)) {
            if (file_put_contents($file_path, $content) !== false) {
                $msg = "File saved successfully."; $msg_type = "success";
            } else {
                $msg = "Error saving file."; $msg_type = "error";
            }
        }
    }
    
    if ($action === 'delete') {
        $target = $_POST['target'];
        $file_path = realpath($doc_root . '/' . $target);
        if ($file_path && strpos($file_path, $doc_root) === 0 && $file_path !== $doc_root) {
            is_dir($file_path) ? exec("rm -rf " . escapeshellarg($file_path)) : unlink($file_path);
            $msg = "Item deleted."; $msg_type = "success";
        }
    }

    if ($action === 'mkdir') {
        $new = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['new_name']);
        if ($new) { mkdir($current_dir . '/' . $new); $msg = "Folder created."; $msg_type = "success"; }
    }

    if ($action === 'mkfile') {
        $new = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['new_name']);
        if ($new) { touch($current_dir . '/' . $new); $msg = "File created."; $msg_type = "success"; }
    }
    
    if ($action === 'rename') {
        $target = $_POST['target'];
        $new_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_POST['new_name']);
        $old_path = realpath($doc_root . '/' . $target);
        if ($old_path && strpos($old_path, $doc_root) === 0 && $new_name) {
            rename($old_path, dirname($old_path) . '/' . $new_name);
            $msg = "Renamed successfully."; $msg_type = "success";
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stackrium FM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0d1117] text-gray-300 min-h-screen font-sans antialiased">
<nav class="bg-gray-900 border-b border-gray-800 px-6 py-3 flex justify-between items-center shadow-lg">
    <div class="flex items-center gap-4">
        <div class="h-8 w-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-500/20">S</div>
        <span class="text-lg font-semibold text-white tracking-wide">Stackrium FM <span class="text-gray-500 text-sm font-normal ml-2"><?php echo $domain; ?></span></span>
    </div>
    <a href="?p=" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-sm font-medium rounded-md transition-all shadow border border-gray-700">Go to Root</a>
</nav>

<div class="max-w-7xl mx-auto p-6">
    <?php if ($msg): ?>
    <div class="mb-6 px-4 py-3 rounded border <?php echo $msg_type === 'error' ? 'bg-red-900/50 border-red-500/50 text-red-200' : 'bg-green-900/50 border-green-500/50 text-green-200'; ?>">
        <?php echo $msg; ?>
    </div>
    <?php endif; ?>

    <?php if ($view === 'edit'): ?>
        <div class="flex justify-between items-center mb-4">
            <div class="text-sm text-gray-400">Editing: <span class="text-white font-mono"><?php echo htmlspecialchars($edit_target); ?></span></div>
            <div class="flex gap-2">
                <a href="?p=<?php echo urlencode($rel_path); ?>" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-sm rounded transition-all border border-gray-700">Cancel</a>
                <button onclick="document.getElementById('saveForm').submit();" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded shadow-lg shadow-indigo-500/20 transition-all font-medium">Save Changes</button>
            </div>
        </div>
        <form id="saveForm" method="POST" action="?p=<?php echo urlencode($rel_path); ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="target" value="<?php echo htmlspecialchars($edit_target); ?>">
            <textarea name="content" spellcheck="false" class="w-full h-[70vh] bg-[#0d1117] text-gray-300 font-mono text-sm p-4 rounded-xl border border-gray-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none resize-none shadow-inner leading-relaxed"><?php echo htmlspecialchars(file_get_contents($edit_abs)); ?></textarea>
        </form>
    <?php else: ?>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div class="flex items-center gap-2 text-sm text-gray-400 font-mono bg-gray-900 px-4 py-2 rounded-lg border border-gray-800 shadow-sm">
                <span class="text-indigo-400">/public_html</span>
                <?php if ($rel_path): ?>
                    <span class="text-gray-600">/</span>
                    <span class="text-gray-200"><?php echo htmlspecialchars($rel_path); ?></span>
                <?php endif; ?>
            </div>
            <div class="flex flex-wrap gap-2">
                <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm rounded transition-all border border-gray-700 text-gray-300">Upload File</button>
                <button onclick="document.getElementById('newFolderModal').classList.remove('hidden')" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm rounded transition-all border border-gray-700 text-gray-300">New Folder</button>
                <button onclick="document.getElementById('newFileModal').classList.remove('hidden')" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-sm rounded transition-all border border-gray-700 text-gray-300">New File</button>
            </div>
        </div>

        <div class="bg-gray-900 rounded-xl border border-gray-800 shadow-xl overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-800/50 text-gray-400 uppercase text-xs tracking-wider border-b border-gray-800">
                    <tr>
                        <th class="px-6 py-4 font-medium">Name</th>
                        <th class="px-6 py-4 font-medium w-32">Size</th>
                        <th class="px-6 py-4 font-medium w-48">Modified</th>
                        <th class="px-6 py-4 font-medium w-32 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    <?php if ($rel_path): ?>
                    <tr class="hover:bg-gray-800/30 transition-colors">
                        <td colspan="4" class="px-6 py-3">
                            <a href="?p=<?php echo urlencode($parent_dir); ?>" class="flex items-center gap-2 text-indigo-400 hover:text-indigo-300 font-medium">
                                Go Up
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php
                    $items = scandir($current_dir);
                    $folders = []; $files = [];
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') continue;
                        $abs_path = $current_dir . '/' . $item;
                        if (is_dir($abs_path)) $folders[] = $item; else $files[] = $item;
                    }
                    natcasesort($folders);
                    natcasesort($files);

                    foreach (array_merge($folders, $files) as $item):
                        $abs_path = $current_dir . '/' . $item;
                        $is_dir = is_dir($abs_path);
                        $item_rel = ($rel_path ? $rel_path . '/' : '') . $item;
                        $size = $is_dir ? '-' : round(filesize($abs_path) / 1024, 1) . ' KB';
                        $date = date("M d, Y H:i", filemtime($abs_path));
                    ?>
                    <tr class="hover:bg-gray-800/30 transition-colors group">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <?php if ($is_dir): ?>
                                    <a href="?p=<?php echo urlencode($item_rel); ?>" class="text-gray-200 hover:text-white font-medium">[DIR] <?php echo htmlspecialchars($item); ?></a>
                                <?php else: ?>
                                    <a href="?view=edit&edit=<?php echo urlencode($item_rel); ?>&p=<?php echo urlencode($rel_path); ?>" class="text-gray-300 hover:text-indigo-400 transition-colors"><?php echo htmlspecialchars($item); ?></a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-gray-500 text-sm"><?php echo $size; ?></td>
                        <td class="px-6 py-3 text-gray-500 text-sm"><?php echo $date; ?></td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <?php if (!$is_dir): ?>
                                    <a href="?view=edit&edit=<?php echo urlencode($item_rel); ?>&p=<?php echo urlencode($rel_path); ?>" class="text-indigo-400 hover:text-indigo-300 text-sm font-medium">Edit</a>
                                <?php endif; ?>
                                <button onclick="showRename('<?php echo htmlspecialchars($item_rel); ?>', '<?php echo htmlspecialchars($item); ?>')" class="text-gray-400 hover:text-white text-sm font-medium">Rename</button>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this item?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="target" value="<?php echo htmlspecialchars($item_rel); ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-300 text-sm font-medium">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="uploadModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
                <h3 class="text-lg font-medium text-white mb-4">Upload File</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="upload_file" required class="w-full text-gray-400 text-sm mb-4">
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded text-sm text-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm text-white font-medium">Upload</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="newFolderModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
                <h3 class="text-lg font-medium text-white mb-4">New Folder</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="mkdir">
                    <input type="text" name="new_name" placeholder="Folder Name" required class="w-full bg-gray-950 border border-gray-800 rounded px-4 py-2 text-white text-sm mb-4">
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('newFolderModal').classList.add('hidden')" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded text-sm text-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm text-white font-medium">Create</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="newFileModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
                <h3 class="text-lg font-medium text-white mb-4">New File</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="mkfile">
                    <input type="text" name="new_name" placeholder="filename.php" required class="w-full bg-gray-950 border border-gray-800 rounded px-4 py-2 text-white text-sm mb-4">
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('newFileModal').classList.add('hidden')" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded text-sm text-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm text-white font-medium">Create</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="renameModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
                <h3 class="text-lg font-medium text-white mb-4">Rename</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="rename">
                    <input type="hidden" name="target" id="renameTarget">
                    <input type="text" name="new_name" id="renameInput" required class="w-full bg-gray-950 border border-gray-800 rounded px-4 py-2 text-white text-sm mb-4">
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('renameModal').classList.add('hidden')" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded text-sm text-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm text-white font-medium">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function showRename(targetPath, currentName) {
                document.getElementById('renameTarget').value = targetPath;
                document.getElementById('renameInput').value = currentName;
                document.getElementById('renameModal').classList.remove('hidden');
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