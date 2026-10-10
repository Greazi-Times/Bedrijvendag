<script setup lang="ts">
/**
 * Description editor on TipTap, the engine behind the dashboard's Filament
 * RichEditor. Its schema is the description policy (App\Support\RichText):
 * paragraphs, line breaks, bold, italic, h3/h4, lists and http(s)/mailto
 * links. Anything else in pasted HTML (font sizes, colours, tables, images)
 * has no node in the schema and is dropped; the server sanitizes again.
 */
import {
    PhArrowClockwise,
    PhArrowCounterClockwise,
    PhLink,
    PhLinkBreak,
    PhListBullets,
    PhListNumbers,
    PhTextB,
    PhTextHFour,
    PhTextHThree,
    PhTextItalic,
} from '@phosphor-icons/vue';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { computed, onBeforeUnmount, watch } from 'vue';
import type { Component } from 'vue';
import { useTranslations } from '@/i18n';

const props = defineProps<{
    id: string;
    placeholder?: string;
    labelledBy?: string;
    describedBy?: string;
    invalid?: boolean;
}>();

const model = defineModel<string>({ default: '' });
const emit = defineEmits<{ textLength: [length: number] }>();
const { t } = useTranslations();

const safeUrl = (url: string) => /^(https?:\/\/|mailto:)/i.test(url.trim());

// Word and web pages use any heading level; map them onto the two the policy allows.
function normalizeHeadings(html: string) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    doc.body.querySelectorAll('h1, h2, h5, h6').forEach((heading) => {
        const level = heading.tagName === 'H1' || heading.tagName === 'H2' ? 'h3' : 'h4';
        const replacement = doc.createElement(level);
        replacement.append(...heading.childNodes);
        heading.replaceWith(replacement);
    });
    return doc.body.innerHTML;
}

const editor = useEditor({
    content: model.value,
    extensions: [
        StarterKit.configure({
            heading: { levels: [3, 4] },
            blockquote: false,
            code: false,
            codeBlock: false,
            horizontalRule: false,
            strike: false,
            underline: false,
            link: {
                openOnClick: false,
                autolink: true,
                defaultProtocol: 'https',
                protocols: ['http', 'https', 'mailto'],
                isAllowedUri: (url) => safeUrl(url),
                HTMLAttributes: { target: '_blank', rel: 'noopener noreferrer' },
            },
        }),
    ],
    editorProps: {
        attributes: {
            id: props.id,
            role: 'textbox',
            'aria-multiline': 'true',
            ...(props.labelledBy ? { 'aria-labelledby': props.labelledBy } : {}),
            ...(props.describedBy ? { 'aria-describedby': props.describedBy } : {}),
            class: 'rich min-h-56 px-4 py-3 text-ink outline-none',
        },
        transformPastedHTML: normalizeHeadings,
    },
    onUpdate: ({ editor }) => {
        model.value = editor.isEmpty ? '' : editor.getHTML();
        emit('textLength', editor.getText().trim().length);
    },
    onCreate: ({ editor }) => emit('textLength', editor.getText().trim().length),
});

// Keep the editor in sync when the form resets the value from outside.
watch(model, (value) => {
    const current = editor.value;
    if (current && value !== (current.isEmpty ? '' : current.getHTML())) current.commands.setContent(value, { emitUpdate: false });
});

watch([() => props.invalid, editor], ([invalid]) => editor.value?.view.dom.setAttribute('aria-invalid', invalid ? 'true' : 'false'));

onBeforeUnmount(() => editor.value?.destroy());

function setLink() {
    const current = editor.value?.getAttributes('link').href ?? '';
    const url = window.prompt(t('companyProfile.linkPrompt'), current);
    if (url === null || !editor.value) return;

    const chain = editor.value.chain().focus().extendMarkRange('link');
    if (url.trim() === '') chain.unsetLink().run();
    else if (safeUrl(url)) chain.setLink({ href: url.trim() }).run();
    else window.alert(t('companyProfile.linkInvalid'));
}

type Tool = { label: string; icon: Component; active?: () => boolean; run: () => void; disabled?: () => boolean };

const groups = computed<Tool[][]>(() => {
    const e = editor.value;
    if (!e) return [];
    return [
        [
            { label: t('companyProfile.bold'), icon: PhTextB, active: () => e.isActive('bold'), run: () => e.chain().focus().toggleBold().run() },
            { label: t('companyProfile.italic'), icon: PhTextItalic, active: () => e.isActive('italic'), run: () => e.chain().focus().toggleItalic().run() },
        ],
        [
            {
                label: t('companyProfile.heading'),
                icon: PhTextHThree,
                active: () => e.isActive('heading', { level: 3 }),
                run: () => e.chain().focus().toggleHeading({ level: 3 }).run(),
            },
            {
                label: t('companyProfile.subheading'),
                icon: PhTextHFour,
                active: () => e.isActive('heading', { level: 4 }),
                run: () => e.chain().focus().toggleHeading({ level: 4 }).run(),
            },
        ],
        [
            { label: t('companyProfile.bullets'), icon: PhListBullets, active: () => e.isActive('bulletList'), run: () => e.chain().focus().toggleBulletList().run() },
            { label: t('companyProfile.numbered'), icon: PhListNumbers, active: () => e.isActive('orderedList'), run: () => e.chain().focus().toggleOrderedList().run() },
        ],
        [
            { label: t('companyProfile.insertLink'), icon: PhLink, active: () => e.isActive('link'), run: setLink },
            { label: t('companyProfile.removeLink'), icon: PhLinkBreak, run: () => e.chain().focus().unsetLink().run(), disabled: () => !e.isActive('link') },
        ],
        [
            { label: t('companyProfile.undo'), icon: PhArrowCounterClockwise, run: () => e.chain().focus().undo().run(), disabled: () => !e.can().undo() },
            { label: t('companyProfile.redo'), icon: PhArrowClockwise, run: () => e.chain().focus().redo().run(), disabled: () => !e.can().redo() },
        ],
    ];
});
</script>

<template>
    <div
        class="overflow-hidden rounded-[var(--radius-input)] bg-canvas ring-1 transition-shadow focus-within:ring-2 focus-within:ring-brand dark:bg-surface"
        :class="invalid ? 'ring-danger' : 'ring-hairline'"
    >
        <div class="flex flex-wrap items-center gap-0.5 border-b border-hairline bg-surface px-2 py-1.5" role="toolbar" :aria-label="t('common.description')" :aria-controls="id">
            <template v-for="(group, index) in groups" :key="index">
                <span v-if="index > 0" class="mx-1 h-5 w-px bg-hairline" aria-hidden="true"></span>
                <button
                    v-for="tool in group"
                    :key="tool.label"
                    type="button"
                    class="inline-flex size-8 items-center justify-center rounded-[var(--radius-chip)] text-ink-muted transition-colors hover:bg-surface-2 hover:text-ink disabled:pointer-events-none disabled:opacity-40"
                    :class="tool.active?.() ? 'bg-surface-2 text-ink' : ''"
                    :title="tool.label"
                    :aria-label="tool.label"
                    :aria-pressed="tool.active ? tool.active() : undefined"
                    :disabled="tool.disabled?.()"
                    @click="tool.run"
                >
                    <component :is="tool.icon" :size="18" aria-hidden="true" />
                </button>
            </template>
        </div>

        <div class="relative">
            <p v-if="editor?.isEmpty && placeholder" class="pointer-events-none absolute top-3 left-4 text-base text-ink-subtle" aria-hidden="true">{{ placeholder }}</p>
            <EditorContent :editor="editor" />
        </div>
    </div>
</template>
