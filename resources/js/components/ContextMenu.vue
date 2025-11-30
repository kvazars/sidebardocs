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

<script setup>
import { ref, defineProps, defineEmits } from "vue";
import { useAuthIdStore } from "../stores/authId";

const { actions, x, y, treeUserId } = defineProps([
    "actions",
    "x",
    "y",
    "treeUserId",
]);
const emit = defineEmits(["action-clicked"]);

const emitAction = (action) => {
    emit("action-clicked", action);
};

const store = useAuthIdStore();
</script>
