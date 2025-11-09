</div> <!-- .container -->
    <div class="container">
        <hr class="footer-divider">
    </div>
    <footer id="footer" class="footer mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="footer-section">
                        <h3>🏡 Homestay Management</h3>
                        <p>Quản lý homestay thông minh, đặt phòng dễ dàng.</p>
                        <div class="social-links">
                            <a href="#" title="Facebook" target="_blank"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" title="Instagram" target="_blank"><i class="fab fa-instagram"></i></a>
                            <a href="#" title="YouTube" target="_blank"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="footer-section">
                        <h3>🔗 Liên kết nhanh</h3>
                        <ul class="footer-links">
                            <li><a href="index.php">🏠 <span>Trang chủ</span></a></li>
                            <li><a href="index.php#room-list-section">🔍 <span>Tìm phòng</span></a></li>
                            <li><a href="my_bookings.php">📅 <span>Lịch sử đặt phòng</span></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="footer-section">
                        <h3>📞 Liên hệ</h3>
                        <ul class="footer-links">
                            <li><i class="fas fa-map-marker-alt"></i> Trần Phú, Phước Vĩnh, Thành phố Huế</li>
                            <li><i class="fas fa-phone"></i> 0901 234 567</li>
                            <li><i class="fas fa-envelope"></i> info@homestay.com</li>
                            <li><i class="fas fa-clock"></i> 24/7 Support</li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Đường kẻ ngăn cách -->
            <hr class="footer-divider-inner">
            <div class="footer-map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.502234567!2d106.700423315334!3d10.776889992322!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f1baf1baf1b%3A0x1234567890abcdef!2zMTIzIMSQxrDhu51uZyBBQkMsIFF14bqjbCBYWVosIFRIUC5IQ00!5e0!3m2!1svi!2s!4v1660000000000!5m2!1svi!2s" width="100%" height="350" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.15);" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <!-- Đường kẻ ngăn cách -->
            <hr class="footer-divider-inner">
            <div class="footer-bottom">
                <p style="text-align: center;">&copy; <?= date('Y') ?> © 2025 Homestay Management. Hành trình nghỉ dưỡng bắt đầu từ đây | Thiết kế với  <span style="color:#d4af37;">❤️</span></p>
            </div>
        </div>
    </footer>

<!-- Mobile Menu Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileMenuLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="mobile-nav">
            <a href="index.php" class="d-block p-3">Trang Chủ</a>
            <a href="index.php#room-list-section" class="d-block p-3">Tìm Phòng</a>
            <a href="#footer" class="d-block p-3">Liên Hệ</a>
            <hr>
            <?php if (isset($_SESSION['customer'])): ?>
                <a href="my_bookings.php" class="d-block p-3">Lịch sử đặt phòng</a>
                <a href="logout.php" class="d-block p-3 text-danger">Đăng xuất</a>
            <?php else: ?>
                <a href="#" class="d-block p-3" data-bs-toggle="modal" data-bs-target="#loginModal">Đăng nhập</a>
                <a href="#" class="d-block p-3" data-bs-toggle="modal" data-bs-target="#registerModal">Đăng ký</a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>