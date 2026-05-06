/**
 * Editor.js и инструменты дергают codex-notifier (.cdx-notify внизу экрана).
 * Перехватываем вставку в DOM и показываем те же тексты через общий showToast (верх справа).
 */

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function toastVariantFromNotify(el) {
    if (el.classList.contains("cdx-notify--error")) return "danger";
    if (el.classList.contains("cdx-notify--success")) return "success";
    return "info";
}

function flushNotifyElement(el, showToast) {
    if (!el?.classList?.contains("cdx-notify")) return;
    const variant = toastVariantFromNotify(el);
    const text = (el.innerText || "").trim();
    el.remove();
    if (!text) return;
    showToast(escapeHtml(text), variant);
}

let refCount = 0;
let observer = null;

export function installCodexNotifierToastBridge(showToast) {
    if (typeof showToast !== "function") {
        return () => {};
    }

    refCount += 1;

    if (!observer) {
        observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                for (const node of m.addedNodes) {
                    if (node.nodeType !== Node.ELEMENT_NODE) continue;
                    if (node.classList.contains("cdx-notify")) {
                        flushNotifyElement(node, showToast);
                        continue;
                    }
                    node.querySelectorAll?.(".cdx-notify").forEach((el) => {
                        flushNotifyElement(el, showToast);
                    });
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    return () => {
        refCount -= 1;
        if (refCount <= 0 && observer) {
            observer.disconnect();
            observer = null;
            refCount = 0;
        }
    };
}
