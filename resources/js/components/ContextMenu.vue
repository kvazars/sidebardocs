<template>
    <div
        v-if="$props.treeUserId == store.id || store.role == 'admin'"
        class="fixed h-1/3 z-50 context-menu d-flex"
        :style="{ top: y + 'px', left: x + 'px' }"
    >
        <div
            v-for="action in actions"
            :key="action.action"
            @click="emitAction(action.action)"
            :title="action.label"
        >
            <i :class="'bi bi-' + action.icon"></i>
        </div>
    </div>
</template>

<script>
import { useAuthIdStore } from "../stores/authId";

export default {
    name: "ContextMenu",
    props: ["actions", "x", "y", "treeUserId"],
    emits: ["action-clicked"],
    computed: {
        store() {
            return useAuthIdStore();
        },
    },
    methods: {
        emitAction(action) {
            this.$emit("action-clicked", action);
        },
    },
};
</script>
