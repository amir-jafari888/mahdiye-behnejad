<script>
    document.querySelectorAll('.floating-nav a').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelector('.floating-nav a.active')?.classList.remove('active');
            this.classList.add('active');
        });
    });
</script>
</body>

</html>