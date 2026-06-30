<?php
class KhenThuongModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /* ================== LẤY DANH SÁCH ================== */
    public function getAll($keyword = '', $loai = '', $thang = '') {
    // CHÚ THÍCH THỬ NGHIỆM: Đang thực hiện câu lệnh SQL JOIN 3 bảng để lấy thông tin khen thưởng kỷ luật
    $sql = "SELECT 
                kt.MaKTKL,
                kt.MaNV,
                kt.NgayQuyetDinh,
                kt.HinhThuc,
                kt.SoTien,
                kt.LyDo,
                kt.GhiChu,
                nv.HoTen,
                l.TenLoai,
                l.Loai
            FROM khenthuongkyluat kt
            JOIN nhanvien nv ON kt.MaNV = nv.MaNV
            JOIN loaikhenthuongkyluat l ON kt.MaLoai = l.MaLoai
            WHERE 1=1";

    $params = [];
    $types = "";

    // Tìm kiếm
    if (!empty($keyword)) {
        $sql .= " AND (nv.HoTen LIKE ? OR l.TenLoai LIKE ?)";
        $keyword = "%$keyword%";
        $params[] = $keyword;
        $params[] = $keyword;
        $types .= "ss";
    }

    // Lọc loại
    if (!empty($loai)) {
        $sql .= " AND l.Loai = ?";
        $params[] = $loai;
        $types .= "s";
    }

    // Lọc tháng
    if (!empty($thang)) {
        $sql .= " AND DATE_FORMAT(kt.NgayQuyetDinh, '%Y-%m') = ?";
        $params[] = $thang;
        $types .= "s";
    }

    $sql .= " ORDER BY kt.NgayQuyetDinh DESC";

    $stmt = mysqli_prepare($this->conn, $sql);

    if (!$stmt) {
        die("Lỗi SQL: " . mysqli_error($this->conn));
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}
    /* ================== THÊM ================== */
    public function insert($data) {
        // CHÚ THÍCH THỬ NGHIỆM: Hàm này dùng để lưu quyết định khen thưởng/kỷ luật mới vào DB
        $sql = "INSERT INTO khenthuongkyluat
                (MaNV, MaLoai, NgayQuyetDinh, HinhThuc, SoTien, LyDo, GhiChu)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "iissdss",
            $data['MaNV'],
            $data['MaLoai'],
            $data['NgayQuyetDinh'],
            $data['HinhThuc'],
            $data['SoTien'],
            $data['LyDo'],
            $data['GhiChu']
        );

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $result;
    }

    /* ================== LẤY THEO ID ================== */
    public function getById($id) {

        $sql = "SELECT * FROM khenthuongkyluat WHERE MaKTKL = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);
        return $data;
    }

    /* ================== CẬP NHẬT ================== */
    public function update($data) {

        $sql = "UPDATE khenthuongkyluat SET
                MaNV = ?,
                MaLoai = ?,
                NgayQuyetDinh = ?,
                HinhThuc = ?,
                SoTien = ?,
                LyDo = ?,
                GhiChu = ?
                WHERE MaKTKL = ?";

        $stmt = mysqli_prepare($this->conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "iissdssi",
            $data['MaNV'],
            $data['MaLoai'],
            $data['NgayQuyetDinh'],
            $data['HinhThuc'],
            $data['SoTien'],
            $data['LyDo'],
            $data['GhiChu'],
            $data['MaKTKL']
        );

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $result;
    }

    /* ================== XOÁ ================== */
    public function delete($id) {

        $sql = "DELETE FROM khenthuongkyluat WHERE MaKTKL = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $result;
    }

    /* ================== LẤY DANH SÁCH NHÂN VIÊN ================== */
    public function getNhanVien() {
        return mysqli_query($this->conn,
            "SELECT MaNV, HoTen FROM nhanvien ORDER BY HoTen ASC");
    }

    /* ================== LẤY DANH SÁCH LOẠI ================== */
    public function getLoai() {
        return mysqli_query($this->conn,
            "SELECT MaLoai, TenLoai, Loai
             FROM loaikhenthuongkyluat
             ORDER BY Loai, TenLoai");
    }
    public function getTongTien($keyword = '', $loai = '', $thang = '') {
    // CHÚ THÍCH THỬ NGHIỆM: Hàm dùng để tính tổng số tiền đã thưởng và phạt để làm báo cáo
    $sql = "SELECT 
                SUM(CASE WHEN l.Loai = 'Khen thưởng' THEN kt.SoTien ELSE 0 END) AS TongThuong,
                SUM(CASE WHEN l.Loai = 'Kỷ luật' THEN kt.SoTien ELSE 0 END) AS TongPhat
            FROM khenthuongkyluat kt
            JOIN nhanvien nv ON kt.MaNV = nv.MaNV
            JOIN loaikhenthuongkyluat l ON kt.MaLoai = l.MaLoai
            WHERE 1=1";

    $params = [];
    $types = "";

    if (!empty($keyword)) {
        $sql .= " AND (nv.HoTen LIKE ? OR l.TenLoai LIKE ?)";
        $keyword = "%$keyword%";
        $params[] = $keyword;
        $params[] = $keyword;
        $types .= "ss";
    }

    if (!empty($loai)) {
        $sql .= " AND l.Loai = ?";
        $params[] = $loai;
        $types .= "s";
    }

    if (!empty($thang)) {
        $sql .= " AND DATE_FORMAT(kt.NgayQuyetDinh, '%Y-%m') = ?";
        $params[] = $thang;
        $types .= "s";
    }

    $stmt = mysqli_prepare($this->conn, $sql);

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $data;
    //Nguyễn Thanh Bình sờ tu bítádsadsa
}
}
?>