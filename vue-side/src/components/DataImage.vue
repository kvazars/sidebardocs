<template>
	<img :src="imageAsBase64" />
</template>

<script>
export default {
	props: ["src", "datasend"],
	data() {
		return {
			imageAsBase64: "",
		};
	},
	mounted() {
		let form = new FormData();
		form.append("image", this.src);
		this.datasend("getImage", "POST", form)
			.then((res) => {
				console.log(res);
				
				this.imageAsBase64 = res.image;
			})
			.catch((error) => {
				console.log(error);
			});
	},
};
</script>
