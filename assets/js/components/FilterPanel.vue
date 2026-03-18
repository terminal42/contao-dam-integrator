<template>
    <div>
        <button type="button" class="close" aria-controls="tl_content_filter" data-action="contao--toggle-receiver#close" data-contao--tooltips-target="tooltip" :title="labels.toggleFilterHide">×</button>
        <div class="tl_formbody" ref="filterPanel">
            <div class="tl_panel">
                <fieldset class="tl_search tl_subpanel">
                    <legend>{{ labels.search }}</legend>
                    <label for="tl_search">{{ labels.field }}</label>
                    <div class="tl_select_wrapper" data-controller="contao--choices">
                        <select name="tl_field" id="tl_search" class="tl_select" :class="{ active: keywords !== '' }">
                            <option value="keywords">{{ labels.keywords }}</option>
                        </select>
                    </div>
                    <label for="tl_search_term">{{ labels.keyword }}</label>
                    <input type="search" name="tl_value" id="tl_search_term" class="tl_text" :class="{ active: keywords !== '' }" v-model="keywords" @keyup="applyFiltersDebounced()">
                </fieldset>
            </div>
            <div v-if="hasFilters()" class="tl_panel">
                <fieldset class="tl_filter tl_subpanel">
                    <legend>{{ labels.filter }}</legend>

                    <template v-for="(filter, property) in filters">
                        <label :for="`tl_filter_${property}`">{{ filter.label }}</label>
                        <div class="tl_select_wrapper" data-controller="contao--choices">
                            <select v-model="filterData[property]" :name="property" :id="`tl_filter_${property}`" class="tl_select" :class="{ active: isFilterActive(property)}" @change="applyFilters()">
                                <option v-for="option in filter.options" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                    </template>
                </fieldset>
            </div>
            <div class="tl_panel">
                <pagination-drop-down :data="pagination" :labels="labels" @apply="updatePagination"></pagination-drop-down>
            </div>
        </div>
        <div class="tl_submit_panel tl_subpanel" style="grid-template-columns:repeat(1,1fr)">
            <button name="filter_reset" id="filter_reset" value="1" class="tl_submit filter_reset" @click="resetFilters" :disabled="!resetFiltersActive">{{ labels.reset }}</button>
        </div>
    </div>
</template>

<script>
import debounce from 'lodash.debounce';
import PaginationDropDown from './PaginationDropDown.vue';

export default {
    props: {
        filterDefinition: {
            type: Array,
            required: true,
        },
        labels: {
            type: Object,
            required: true,
        },
        pagination: {
            type: Object,
            required: true,
        },
    },

    components: { PaginationDropDown },

    data() {
        return {
            filterData: {},
            keywords: '',
        };
    },

    computed: {
        filters() {
            let filters = {};

            this.filterDefinition.forEach((filterDef) => {
                filters[filterDef.propertyName] = {
                    label: filterDef.label,
                    options: [{ value: '', label: '-' }, ...filterDef.options],
                };

                // Set default selected option
                this.filterData[filterDef.propertyName] = '';
            });

            return filters;
        },

        resetFiltersActive() {
            return this.isAtLeastOneFilterOrKeywordsActive();
        },
    },

    methods: {
        hasFilters() {
            return Object.keys(this.filters).length !== 0;
        },

        applyFiltersDebounced: debounce(function () {
            this.applyFilters();
        }, 500),

        applyFilters() {
            this.$forceUpdate();
            if (this.isAtLeastOneFilterOrKeywordsActive()) {
                this.$emit('apply', this.filterData, this.keywords);
            } else {
                this.$emit('reset');
            }
        },

        isFilterActive(property) {
            return '' !== this.filterData[property];
        },

        isAtLeastOneFilterOrKeywordsActive() {
            let hasFilters = false;

            Object.keys(this.filters).forEach((property) => {
                if (!hasFilters && this.isFilterActive(property)) {
                    hasFilters = true;
                }
            });

            return '' !== this.keywords || hasFilters;
        },

        resetFilters() {
            Object.keys(this.filters).forEach((property) => {
                // Set default selected option
                this.filterData[property] = '';
            });
            this.keywords = '';

            // Refresh the Choices.js – as silly as it is, but there is no way to access the Choices instance
            this.$refs.filterPanel.querySelectorAll('select').forEach((el) => {
                const item = el.parentElement.querySelector('.choices__item');

                if (item) {
                    item.textContent = '-';
                }
            });

            this.$emit('reset');
        },

        updatePagination(page) {
            this.$emit('paginationUpdated', page);
        },
    },
};
</script>
