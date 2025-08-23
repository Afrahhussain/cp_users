<script>
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector(".sidebar");
  const toggleBtn = document.querySelector(".sidebar-toggle");
  sidebar.classList.add("collapsed"); // collapsed by default
  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
  });
});
</script>
</body>
</html>
