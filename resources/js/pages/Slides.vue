<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';

const fallbackImages = [
    '/images/slides/Bedrijvendag-maart-26-2026-3.png',
    '/images/slides/Bedrijvendag-maart-26-2026-4.png',
    '/images/slides/Bedrijvendag-maart-26-2026-5.png',
    '/images/slides/Bedrijvendag-maart-26-2026-6.png',
    '/images/slides/Bedrijvendag-maart-26-2026-7.png',
];

const props = defineProps<{
    slideImages: string[];
}>();

const images = computed(() => (props.slideImages.length > 0 ? props.slideImages : fallbackImages));
const currentIndex = ref(0);
let intervalId: ReturnType<typeof setInterval> | null = null;

function preloadImages() {
    images.value.forEach((src) => {
        const img = new Image();
        img.src = src;
    });
}

onMounted(() => {
    preloadImages();

    intervalId = setInterval(() => {
        currentIndex.value = (currentIndex.value + 1) % images.value.length;
    }, 8000);
});

onBeforeUnmount(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script>

<template>
    <div class="slideshow">
        <Transition name="fade" mode="out-in">
            <img :key="images[currentIndex]" :src="images[currentIndex]" alt="" class="slide-image" />
        </Transition>
    </div>
</template>

<style scoped>
.slideshow {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background: #000;
}

.slide-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Fade animation */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-enter-to,
.fade-leave-from {
    opacity: 1;
}
</style>
