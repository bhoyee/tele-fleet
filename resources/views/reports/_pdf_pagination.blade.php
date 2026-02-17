<script type="text/php">
    if (isset($pdf)) {
        $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
        $font = $fontMetrics->get_font("Helvetica", "normal");
        $size = 9;
        $color = [107, 114, 128];
        $x = $pdf->get_width() - 90;
        $y = $pdf->get_height() - 28;
        $pdf->page_text($x, $y, $text, $font, $size, $color);
    }
</script>

