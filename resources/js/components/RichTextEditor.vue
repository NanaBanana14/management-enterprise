<script setup lang="ts">
import { Button } from '@/components/ui/button';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { Bold, Heading2, Italic, Link as LinkIcon, List, ListOrdered, Redo, Undo } from 'lucide-vue-next';
import { watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        placeholder?: string;
    }>(),
    { placeholder: 'Write the material content...' },
);

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Link.configure({ openOnClick: false, autolink: true }),
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    editorProps: {
        attributes: {
            class: 'rich-text-content min-h-32 px-3 py-2 text-sm focus:outline-none',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && value !== editor.value.getHTML()) {
            editor.value.commands.setContent(value, { emitUpdate: false });
        }
    },
);

function setLink() {
    if (!editor.value) return;

    const previousUrl = editor.value.getAttributes('link').href as string | undefined;
    const url = window.prompt('URL', previousUrl ?? '');

    if (url === null) return;

    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}
</script>

<template>
    <div class="rounded-md border border-input bg-background shadow-sm">
        <div v-if="editor" class="flex flex-wrap items-center gap-1 border-b border-input p-1">
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('bold') }"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('italic') }"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('heading', { level: 2 }) }"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
            >
                <Heading2 class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('bulletList') }"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('orderedList') }"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('link') }"
                @click="setLink"
            >
                <LinkIcon class="size-4" />
            </Button>
            <div class="mx-1 h-5 w-px bg-border" />
            <Button type="button" variant="ghost" size="icon" class="size-8" :disabled="!editor.can().undo()" @click="editor.chain().focus().undo().run()">
                <Undo class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" :disabled="!editor.can().redo()" @click="editor.chain().focus().redo().run()">
                <Redo class="size-4" />
            </Button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
