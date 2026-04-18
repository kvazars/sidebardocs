import { reactive } from "vue";

export const confirmState = reactive({
    visible: false,
    title: "Подтверждение",
    message: "",
    confirmText: "Подтвердить",
    cancelText: "Отмена",
    resolve: null,
});

export function openConfirmDialog(options = {}) {
    const {
        title = "Подтверждение",
        message = "",
        confirmText = "Подтвердить",
        cancelText = "Отмена",
    } = options;

    if (confirmState.resolve) {
        confirmState.resolve(false);
    }

    confirmState.visible = true;
    confirmState.title = title;
    confirmState.message = message;
    confirmState.confirmText = confirmText;
    confirmState.cancelText = cancelText;

    return new Promise((resolve) => {
        confirmState.resolve = resolve;
    });
}

export function closeConfirmDialog(result = false) {
    const resolver = confirmState.resolve;

    confirmState.visible = false;
    confirmState.message = "";
    confirmState.resolve = null;

    if (resolver) {
        resolver(result);
    }
}
