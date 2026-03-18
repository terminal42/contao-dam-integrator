<template>
    <li class="tl_file click2edit toggle_select hover-div">
        <thumbnail :asset="asset"></thumbnail>
        <div class="tl_right download-button">
            <button v-if="!asset.downloaded" class="button" @click="downloadAsset()" :disabled="this.isDownloading" data-contao--tooltips-target="tooltip" :title="labels.download.replace('%s', `&quot;${asset.name}.${asset.extension}&quot;`)">
                <img src="../../img/download.svg" width="20" height="20" alt="" v-if="!this.isDownloading">
                <img src="/system/themes/flexible/icons/loading.svg" width="20" height="20" alt="" class="color-scheme--light" v-if="this.isDownloading">
                <img src="/system/themes/flexible/icons/loading--dark.svg" width="20" height="20" alt="" class="color-scheme--dark" v-if="this.isDownloading">
            </button>
            <radio v-if="fieldType === 'radio'" name="picker" :value="asset.uuid" :checked="asset.selected" :disabled="!asset.downloaded"></radio>
            <checkbox v-else name="picker[]" :value="asset.uuid" :checked="asset.selected" :disabled="!asset.downloaded"></checkbox>
        </div>
    </li>
</template>

<script>
    import Thumbnail from './Thumbnail.vue';
    import Radio from './Radio.vue';
    import Checkbox from './Checkbox.vue';
    export default {
        props: {
            apiUrl: {
                type: String,
                required: true,
            },
            fieldType: {
                type: String,
                required: true,
            },
            asset: {
                type: Object,
                required: true,
            },
            labels: {
                type: Object,
                required: true,
            },
        },

        components: { Thumbnail, Radio, Checkbox },

        data() {
            return {
                isDownloading: false,
            }
        },

        methods: {
            downloadAsset() {
                if (this.asset.downloaded || this.isDownloading) {
                    return;
                }

                this.isDownloading = true;

                fetch(this.apiUrl, {
                  method: 'POST',
                  body: JSON.stringify({ identifier: this.asset.identifier }),
                  headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                  }
                })
                .then((response) => {
                  return response.json();
                })
                .then((data) => {
                      if ('OK' === data.status) {
                          this.asset.uuid = data.uuid;
                          this.asset.downloaded = true;
                          this.asset.selected = true;
                      } else {
                          throw new Error(`asset download failed with status ${data.status}`);
                      }
                  }
                ).catch(() => {
                    alert(this.labels.downloadFailed);
                }).finally(() => {
                    this.isDownloading = false;
                });
            }
        }
    }
</script>
