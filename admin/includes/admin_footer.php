    </main><!-- /.admin-main -->

    <script>
    // ===== Sidebar Toggle (Mobile) =====
    (function(){
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const hamburger = document.getElementById('hamburgerBtn');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('open');
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        }

        if (hamburger) hamburger.addEventListener('click', openSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);

        // Close sidebar on nav click (mobile)
        document.querySelectorAll('.sidebar-nav a').forEach(function(a) {
            a.addEventListener('click', function() {
                if (window.innerWidth <= 900) closeSidebar();
            });
        });
    })();

    // ===== Auto-dismiss flash messages =====
    (function(){
        document.querySelectorAll('.flash').forEach(function(el) {
            setTimeout(function() {
                el.style.transition = 'opacity 0.4s, transform 0.4s';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-10px)';
                setTimeout(function() { el.remove(); }, 400);
            }, 5000);
        });
    })();

    // ===== Confirm Delete =====
    function confirmDelete(msg) {
        return confirm(msg || 'Estas seguro de que deseas eliminar este elemento?');
    }

    // ===== AJAX Helper =====
    function adminFetch(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(function(r) { return r.json(); });
    }
    </script>
</body>
</html>
