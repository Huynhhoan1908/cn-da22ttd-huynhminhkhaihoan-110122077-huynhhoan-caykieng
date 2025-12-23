<?php
$uploadDirectory = 'uploads/';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxFileSize = 5 * 1024 * 1024;

function error($msg) {
    header('Content-Type: application/json');
    echo json_encode(['error' => ['message' => $msg]]);
    exit;
}

$file = null;
if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['upload'];
} elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
} else {
    error('File upload bị lỗi hoặc không có dữ liệu.');
}

$file_size = $file['size'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($file_ext, $allowedExtensions)) {
    error('Chỉ chấp nhận file ảnh: JPG, PNG, GIF, WEBP.');
}
if ($file_size > $maxFileSize) {
    error('Kích thước file quá lớn (tối đa 5MB).');
}
$new_file_name = uniqid('post_') . '.' . $file_ext;
$target_file = $uploadDirectory . $new_file_name;
if (move_uploaded_file($file['tmp_name'], $target_file)) {
    $url = 'uploads/' . $new_file_name;
    header('Content-Type: application/json');
    echo json_encode(['url' => $url]);
    exit;
} else {
    error('Lỗi khi di chuyển file (Kiểm tra quyền ghi thư mục uploads/).');
}