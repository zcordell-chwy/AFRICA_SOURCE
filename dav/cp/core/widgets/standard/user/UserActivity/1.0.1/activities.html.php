<? if ($this->data['attrs']['label_title']): ?>
<h2>
    <?= $this->data['attrs']['label_title'] ?>
</h2>
<? endif; ?>

<? foreach ($this->data['activityOrdering'] as $ordering): ?>
    <? /* Skip activities that happen on the same object, to avoid redundancy */ ?>
    <? if ($lastActivity && $lastActivity['id'] === $ordering['id']) continue; ?>
    <div class="rn_Activity rn_<?= ucfirst($ordering['type']) ?>" itemscope itemtype="http://schema.org/Question">
        <? if ($lastActivity['type'] !== $ordering['type']): ?>
        <rn:block id="activityTitle">
        <div class="rn_ActivityTitle">
            <span class="rn_ActionLabel">
                <?= $this->helper->labelForActivity($ordering['type'], $this->data['attrs']) ?>
            </span>
        </div>
        </rn:block>
        <? endif; ?>

        <?= $this->render($ordering['type'], array(
            'action' => $this->data['activity'][$ordering['index']],
         )) ?>
    </div>
    <? $lastActivity = $ordering; ?>
<? endforeach; ?>
