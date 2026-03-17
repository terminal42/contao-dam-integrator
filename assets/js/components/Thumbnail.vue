<template>
    <div class="tl_left"> {{ asset.name }} <span class="tl_gray">{{ asset.meta }}</span><br>
        <img class="preview-image" v-if="asset.thumb"
                :src="asset.thumb.url"
                :width="asset.thumb.width"
                :height="asset.thumb.height"
                :alt="asset.thumb.alt"
                style="margin:5px 0 2px -19px"
        >
        <div><span class="tl_gray">ID: {{ asset.identifier }}</span></div>
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
