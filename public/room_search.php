<?php

/**
 * Tìm các phòng còn trống dựa trên ngày nhận và trả phòng.
 *
 * @param mysqli $conn Đối tượng kết nối cơ sở dữ liệu.
 * @param string $checkin Ngày nhận phòng (định dạng 'Y-m-d').
 * @param string $checkout Ngày trả phòng (định dạng 'Y-m-d').
 * @return mysqli_result|false Kết quả truy vấn chứa các phòng còn trống, hoặc false nếu có lỗi.
 */
function findAvailableRooms(mysqli $conn, string $checkin, string $checkout, string $keyword = '', ?int $limit = null, ?int $offset = null)
{
    $whereConditions = [];
    $params = [];
    $types = "";

    $sql = "
        SELECT
            r.*, 
            (r.quantity - IFNULL(booked_rooms.booked_count, 0)) as available_quantity
        FROM
            rooms r
        LEFT JOIN
            (
                SELECT room_id, COUNT(id) as booked_count
                FROM bookings
                WHERE status IN ('pending', 'confirmed')
                AND checkin < ? AND checkout > ?
                GROUP BY room_id
            ) as booked_rooms ON r.id = booked_rooms.room_id
    ";

    // Luôn thêm điều kiện ngày vào truy vấn con
    $params[] = $checkout;
    $params[] = $checkin;
    $types .= "ss";

    if (!empty($keyword)) {
        $whereConditions[] = "(r.room_name LIKE ? OR r.description LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
        $types .= "ss";
    }

    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }

    $sql .= "
        GROUP BY
            r.id
        HAVING
            available_quantity > 0
        ORDER BY r.id DESC
    ";

    if ($limit !== null && $offset !== null) {
        $sql .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $limit;
        $types .= "ii";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // Ghi lại lỗi nếu cần thiết, ví dụ: error_log($conn->error);
        return false;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}

/**
 * Lấy tất cả các phòng đang có sẵn.
 *
 * @param mysqli $conn Đối tượng kết nối cơ sở dữ liệu.
 * @param string $keyword Từ khóa tìm kiếm (tùy chọn).
 * @return mysqli_result|false Kết quả truy vấn.
 */
function getAllAvailableRooms(mysqli $conn, string $keyword = '', ?int $limit = null, ?int $offset = null)
{
    $whereClause = "";
    $params = [];
    $types = "";

    if (!empty($keyword)) {
        $whereClause = "WHERE (r.room_name LIKE ? OR r.description LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
        $types = "ss";
    }

    $sql = "
        SELECT
            r.*,
            (r.quantity - IFNULL(booked.booked_count, 0)) AS available_quantity
        FROM
            rooms r
        LEFT JOIN (
            SELECT room_id, COUNT(id) AS booked_count
            FROM bookings
            WHERE status IN ('pending', 'confirmed')
            GROUP BY room_id
        ) AS booked ON r.id = booked.room_id
        " . $whereClause . "
        ORDER BY r.id DESC
    ";

    if ($limit !== null && $offset !== null) {
        $sql .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $limit;
        $types .= "ii";
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // Ghi lại lỗi để debug
        error_log("SQL prepare error: " . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}