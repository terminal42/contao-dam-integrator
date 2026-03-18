<template>
    <div class="tl_left">
        <img src="/system/themes/flexible/icons/image--dark.svg" width="16" height="16" alt="" class="color-scheme--dark" loading="lazy">
        <img src="/system/themes/flexible/icons/image.svg" width="16" height="16" alt="" class="color-scheme--light" loading="lazy">

        {{ asset.name }}.{{ asset.extension }}

        <span class="tl_gray">({{ asset.meta }})</span>

        <br>

        <span class="tl_help">ID: {{ asset.identifier }}</span>

        <br>

        <img class="preview-image" v-if="asset.thumb"
                :src="asset.thumb.url"
                :width="asset.thumb.width"
                :height="asset.thumb.height"
                :alt="asset.thumb.alt"
                loading="lazy"
        >
    </div>
</template>

<script>
export default {
    props: {
        asset: {
            type: Object,
            required: true,
        },
    },
};
</script>
