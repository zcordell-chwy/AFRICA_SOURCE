<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <section>
        <div id="rn_<?= $this->instanceID ?>_Filters">
            <!-- <rn:widget path="search/FilterDropdown" filter_name="AgeGroup" report_id="101808" /> -->
            <rn:widget path="search/FilterDropdown" filter_name="Gender" report_id="101808" />
            <rn:widget path="search/FilterDropdown" filter_name="BirthMonth" report_id="101808" />
            <rn:widget path="search/FilterDropdown" filter_name="BirthYear" report_id="101808" />
            <rn:widget path="search/FilterDropdown" filter_name="Community" report_id="101808" />
            <!-- <rn:widget path="search/AdvancedSearchDialog" report_id="101808"/> -->

            <!-- TODO: Make Filters -->

            <div>

            </div>

            <!-- <select name="birthMonth" id="birthMonth"> -->
                <? for ($i = 1; $i < 13; $i++) : ?>
                    <!-- <option value="<?= $i ?>"><?= date("F", mktime(0, 0, 0, $i, 10)) ?></option> -->
                <? endfor; ?>
            <!-- </select> -->
        </div>
        <div>
            <ul id="rn_<?= $this->instanceID ?>_Carousel">
            </ul>
            <div class="carousel_controls_container">
                <button id="rn_<?= $this->instanceID ?>_Back" class="carousel_controls">&laquo; Back</button>
                <button id="rn_<?= $this->instanceID ?>_Next" class="carousel_controls">Next &raquo;</button>
            </div>
        </div>
    </section>
</div>