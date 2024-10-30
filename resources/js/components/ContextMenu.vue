<template>
	<div
		class="fixed h-1/3 z-50 context-menu d-flex"
		:style="{ top: y + 'px', left: x + 'px' }"
	>
		<div
			v-for="action in actions"
			:key="action.action"
			@click="emitAction(action.action)"
			:title="action.label"
		>
			<i :class="'fa fa-'+action.icon"></i>
		</div>
	</div>
</template>

<script setup>
import { ref, defineProps, defineEmits } from "vue";

const { actions, x, y } = defineProps(["actions", "x", "y"]);
const emit = defineEmits(["action-clicked"]);

const emitAction = (action) => {
	emit("action-clicked", action);
};

</script>
