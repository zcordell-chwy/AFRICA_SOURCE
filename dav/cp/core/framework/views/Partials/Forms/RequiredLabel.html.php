<?
$ariaAttribute = '';
if(get_instance()->meta['template'] !== 'basic.php') {
    $ariaLabel = $screenReaderLabel ?: \RightNow\Utils\Config::getMessage(REQUIRED_LBL);
    $ariaAttribute = 'aria-label="' . $ariaLabel . '"';
}
?>
<span class="rn_Required" <?= $ariaAttribute ?>>
    <?= $requiredLabel ?: \RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL) ?>
</span>