<template>
	<CFooter class="px-4">
		<div>
			
			<span class="ms-1">&copy; {{ new Date().getFullYear() }} {{ about.name }}.</span>
		</div>
		
	</CFooter>
</template>
<script>
export default {
	props: ["about"]
}
</script>