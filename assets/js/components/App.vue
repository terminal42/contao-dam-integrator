<template>
    <div id="dam-asset-mgmt">
        <div class="tree-view">
            <filter-panel
                    id="tl_content_filter"
                    class="content-filter"
                    data-controller="contao--toggle-receiver"
                    data-contao--toggle-receiver-active-class="active"
                    data-contao--toggle-receiver-contao--toggle-sender-outlet=".header_filter_toggle"
                    data-action="click@document->contao--toggle-receiver#documentClick keydown.esc@document->contao--toggle-receiver#close"
                    :filterDefinition="filterDefinition"
                    :labels="labels"
                    :pagination="pagination"
                    @apply="applyFilter"
                    @reset="resetFilter"
                    @paginationUpdated="paginationUpdated">
            </filter-panel>
            <div class="content-inner">
                <div class="operations" id="tl_buttons">
                    <ul>
                        <li style="display: none;">
                            <button class="header_filter_toggle" :title="labels.toggleFilterShow" data-controller="contao--toggle-sender" data-contao--toggle-sender-contao--toggle-receiver-outlet="#tl_content_filter" :data-contao--toggle-sender-active-title-value="labels.toggleFilterHide" :data-contao--toggle-sender-inactive-title-value="labels.toggleFilterShow" data-action="contao--toggle-sender#toggle:prevent">{{ labels.toggleFilter }}</button>
                        </li>
                    </ul>
                </div>

                <div class="tl_listing_container tree_view tl_file_manager" id="tl_listing" :data-picker-value="fieldType === 'checkbox' ? JSON.stringify(preSelected) : ''">
                    <div v-if="loading" class="loader">{{ labels.loadingData }}</div>
                    <div v-else-if="!hasAssets()">{{ labels.noResult }}</div>
                    <ul v-else class="tl_listing picker unselectable" id="tl_select">
                        <li class="tl_folder_top cf"><div class="tl_left">{{ labels.pickerLabel }}</div></li>
                        <asset-row :apiUrl="this.api.download" :asset="asset" :fieldType="fieldType" :labels="labels" v-for="asset in assets" :key="asset.identifier"></asset-row>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import AssetRow from './AssetRow.vue';
import FilterPanel from './FilterPanel.vue';

export default {
    props: {
        fieldType: {
            type: String,
            required: true,
        },
        labels: {
            type: Object,
            required: true,
        },
        preSelected: {
            type: Array,
            required: true,
        },
        pickerConfig: {
            type: String,
            required: true,
        },
        api: {
            type: Object,
            required: true,
        },
    },

    components: { FilterPanel, AssetRow },

    data() {
        return {
            filterDefinition: [],
            pagination: {},
            assets: [],
            loading: false,
            lastQueryString: '',
            assetsQuery: {
                filters: {},
                keywords: '',
            },
        };
    },

    created() {
        fetch(this.api.filters + '?picker=' + this.pickerConfig)
            .then((response) => {
                return response.json();
            })
            .then((data) => {
                this.filterDefinition = data;
            });

        this.updateAssets();
    },

    methods: {
        hasAssets() {
            return this.assets.length !== 0;
        },

        applyFilter(filters, keywords) {
            this.assetsQuery.filters = filters;
            this.assetsQuery.keywords = keywords;
            this.pagination.currentPage = 1;

            this.updateAssets();
        },

        resetFilter() {
            this.assetsQuery.filters = {};
            this.assetsQuery.keywords = '';
            this.pagination.currentPage = 1;

            this.updateAssets();
        },

        updateAssets() {
            if (this.loading) {
                return;
            }

            if (undefined === this.pagination.currentPage) {
                this.pagination.currentPage = 1;
            }

            let queryString = {
                preSelected: this.preSelected.join(','),
                page: this.pagination.currentPage,
                picker: this.pickerConfig,
            };
            let filters = {};

            if ('' !== this.assetsQuery.keywords) {
                queryString['keyword'] = this.assetsQuery.keywords;
            }

            Object.keys(this.assetsQuery.filters).forEach((property) => {
                let filterValue = this.assetsQuery.filters[property];

                if ('' !== filterValue) {
                    filters[property] = filterValue;
                }
            });

            if (Object.keys(filters).length) {
                queryString['filters'] = JSON.stringify(filters);
            }

            queryString = this.buildQueryString(queryString);

            if (this.lastQueryString === queryString) {
                return;
            }

            this.loading = true;
            this.lastQueryString = queryString;

            let uri = this.api.assets + ('' !== queryString ? '?' + queryString : '');

            fetch(uri)
                .then((response) => {
                    return response.json();
                })
                .then((data) => {
                    this.assets = data.assets;
                    this.pagination = data.pagination;
                    this.loading = false;
                })
                .catch(() => {
                    this.loading = false;
                });
        },

        paginationUpdated() {
            this.updateAssets();
        },

        buildQueryString(data) {
            return Object.keys(data)
                .map(function (key) {
                    return [key, data[key]].map(encodeURIComponent).join('=');
                })
                .join('&');
        },
    },
};
</script>
