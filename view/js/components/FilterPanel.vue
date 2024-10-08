<template>
    <div>
        <div class="tl_panel cf">
            <div class="tl_search tl_subpanel">
                <strong>{{ labels.search }}:</strong>
                <select name="tl_field" :class="{ tl_select: true, active: keywords !== '' }">
                    <option value="keywords">{{ labels.keywords }}</option>
                </select>
                <span>=</span>
                <input type="search" name="tl_value" :class="{ tl_text: true, active: keywords !== '' }" v-model="keywords" @keyup="applyFiltersDebounced()">
            </div>
        </div>
        <div v-if="hasFilters()" class="tl_panel cf">
            <div class="tl_filter tl_subpanel">
                <strong>{{ labels.filter }}:</strong>
                <select v-model="filterData[property]" v-for="(options, property) in filters" :name="property" :class="{ tl_select: true, active: isFilterActive(property)}" @change="applyFilters()">
                    <option v-for="option in options" :value="option.value">{{ option.label }}</option>
                </select>
            </div>
        </div>
        <div class="tl_panel cf">
            <div class="tl_submit_panel tl_subpanel" style="min-width:0">
                <button name="filter_reset" id="filter_reset" value="1" class="tl_img_submit filter_reset" title="" @click="resetFilters">{{ labels.reset }}</button>
            </div>
            <pagination-drop-down :data="pagination" :labels="labels" @apply="updatePagination"></pagination-drop-down>
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
            }
        },

        computed: {
            filters() {
                let filters = {};

                this.filterDefinition.forEach((filterDef) => {
                  filters[filterDef.propertyName] = [];

                  // Add label and reset options first
                   filters[filterDef.propertyName].push({
                       label: filterDef.label,
                       value: ''
                   });
                   filters[filterDef.propertyName].push({
                       label: '---',
                       value: ''
                   });

                   filterDef.options.forEach((option) => {
                       filters[filterDef.propertyName].push(option)
                   });

                   // Set default selected option
                   this.filterData[filterDef.propertyName] = '';
                });

                return filters;
            }
        },

        methods: {

            hasFilters() {
                return Object.keys(this.filters).length !== 0;
            },

            applyFiltersDebounced: debounce(function() {
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
                return  '' !== this.filterData[property];
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

                this.$emit('reset');
            },

            updatePagination(page) {
                this.$emit('paginationUpdated', page);
            }
        },
    };
</script>
