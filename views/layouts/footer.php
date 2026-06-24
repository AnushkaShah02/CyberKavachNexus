<?php
// views/layouts/footer.php
use CyberKavach\Nexus\Helpers\SecurityHelper;
?>
    
    <!-- Global Application Scripts -->
    <script src="<?= SecurityHelper::asset('assets/js/app.js') ?>"></script>
    
</body>
</html>
<?php ob_end_flush(); ?>