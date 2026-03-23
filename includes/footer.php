    <footer id="footer" class="footer mt-5">
        <div class="container">
            <div class="row gy-3">
                <div class="col-lg-12 footer-links">
                    <center>
                        <h4>Give us a follow</h4>
                    </center>
                    <div class="row">
                        <div class="d-flex justify-content-center footersocial">
                            <div class="d-flex" style="text-align:center;">
                                <a href="https://www.youtube.com/davidbombal" target="_blank" class="social-icon fab fa-youtube"></a>
                                <a href="https://x.com/davidbombal" target="_blank" class="social-icon"><img src="https://ccnax.com/wp-content/uploads/2025/04/x.png" width="27"></a>
                                <a href="https://www.linkedin.com/in/davidbombal" target="_blank"
                                    class="social-icon fab fa-linkedin-in"></a>
                                <a href="https://www.facebook.com/davidbombal.co" target="_blank"
                                    class="social-icon fab fa-facebook-f"></a>
                                <a href="https://www.instagram.com/davidbombal/" target="_blank"
                                    class="social-icon fab fa-instagram"></a>
                                <a href="https://www.tiktok.com/@davidbombal" target="_blank" class="social-icon fas fa-music"></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <p style="text-align:center;"><a href="https://ccnax.com/terms-and-conditions/">Terms & Conditions</a> <span
                    style="margin-left:5px;margin-right:5px;">|</span> <a
                    href="https://ccnax.com/privacy-policy/">Privacy Policy</a></p>
            <div class="copyright" style="text-align:center;">
                If you have other issues or non-course questions, send us an
            </div>
            <div class="credits" style="text-align:center;">
                email at support@davidbombal.com. ↑
            </div>
        </div>
    </footer>

    <!-- Global Modals -->
    <div id="custom-modal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modal-title" class="modal-title">Confirm Action</h3>
            <div id="modal-message" class="modal-message">Are you sure you want to proceed?</div>
            <input type="text" id="modal-input" class="modal-input app-hidden">
            <div class="modal-actions">
                <button id="modal-cancel" class="modal-btn modal-btn-cancel">Cancel</button>
                <button id="modal-confirm" class="modal-btn modal-btn-primary">Confirm</button>
            </div>
        </div>
    </div>

    <div id="sub-modal" class="modal-overlay sub-modal">
        <div class="modal-content">
            <h3 id="sub-modal-title" class="modal-title">Confirm Action</h3>
            <div id="sub-modal-message" class="modal-message">Are you sure you want to proceed?</div>
            <div class="modal-actions">
                <button id="sub-modal-cancel" class="modal-btn modal-btn-cancel">Cancel</button>
                <button id="sub-modal-confirm" class="modal-btn modal-btn-primary">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Global Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@srexi/purecounterjs/dist/purecounter_vanilla.js"></script>
    <script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
    <script src="assets/js/main.js?v=<?php echo $assetVersion; ?>"></script>
    <script src="script.js?v=<?php echo $assetVersion; ?>"></script>

    <script>
        $(document).ready(function() {
            // Check if we are on login.php or quiz.php
            const isLoginPage = window.location.pathname.toLowerCase().endsWith('login.php');
            const isQuizPage = window.location.pathname.toLowerCase().endsWith('quiz.php');

            // Global Session Handling
            const AUTH_TOKEN_KEY = 'todo_auth_token';
            const token = localStorage.getItem(AUTH_TOKEN_KEY);
            if (token) {
                fetch('/api/auth/me', {
                    headers: { 'Authorization': `Bearer ${token}` }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.email) {
                        $('#session-email').text(data.email).removeClass('d-none');
                    }
                })
                .catch(() => {});
            } else if (!isLoginPage && !isQuizPage) {
                // Only redirect if not on login or quiz page
                window.location.href = '/login.php';
            }

            $('#logout-btn').click(function() {
                localStorage.removeItem(AUTH_TOKEN_KEY);
                window.location.href = '/login.php';
            });

            // Initialize AOS
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 800,
                    easing: 'slide',
                    once: true
                });
            }
        });
    </script>
</body>
</html>
