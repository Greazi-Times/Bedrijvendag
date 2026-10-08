import type { Directive } from 'vue';

/**
 * v-reveal: fades an element up the first time it enters the viewport.
 *
 * The data-reveal attribute is only added on the client, so server-rendered
 * markup and visitors without JavaScript always see the content. The hidden
 * state lives in CSS behind prefers-reduced-motion: no-preference, so reduced
 * motion users never see the effect. Pass a number to stagger: v-reveal="120".
 */
let observer: IntersectionObserver | null = null;

function getObserver(): IntersectionObserver {
    if (!observer) {
        observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) continue;
                    entry.target.classList.add('is-revealed');
                    observer?.unobserve(entry.target);
                }
            },
            { rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
        );
    }

    return observer;
}

export const reveal: Directive<HTMLElement, number | undefined> = {
    mounted(el, binding) {
        if (typeof IntersectionObserver === 'undefined') return;

        if (binding.value) el.style.setProperty('--reveal-delay', `${binding.value}ms`);
        el.setAttribute('data-reveal', '');
        getObserver().observe(el);
    },
    unmounted(el) {
        observer?.unobserve(el);
    },
    getSSRProps() {
        return {};
    },
};
