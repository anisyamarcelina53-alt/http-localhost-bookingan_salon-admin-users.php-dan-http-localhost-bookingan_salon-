        </div> <!-- Close Main Content Holder -->

        <!-- Admin Footer -->
        <footer class="bg-white py-3 px-4 rounded shadow-sm border border-light mt-4 text-center">
            <div class="small text-muted font-outfit">
                &copy; <?= date('Y'); ?> Glowing Grace Salon. Admin Dashboard Panel.
            </div>
        </footer>
    </div> <!-- Close Page Content (#content) -->
</div> <!-- Close Wrapper -->

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>

<!-- Custom JS for Sidebar Toggle -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');

    if (sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', function () {
            sidebar.classList.toggle('active');
        });
    }
});
</script>
</body>
</html>
