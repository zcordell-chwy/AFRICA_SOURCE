<? if (($hasChildren && !$closeList) || (!$hasChildren)): ?>
    <li>
        <a role="button" id="<?= $facetID ?>" class="<?= $facetClass ?>" href="javascript:void(0)">
            <?= $hasChildren ? "<span class='rn_ToggleExpandCollapse rn_FacetExpanded'></span>" : "" ?><span title="<?= $description ?>" class="rn_FacetText"><?= $description ?></span><span class="rn_FacetClearIcon"></span>
        </a>
<? endif; ?>
<? if ($closeList): ?>
    </li>
<? endif; ?>
