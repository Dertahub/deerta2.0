<template>
	<view>
		<u-toast ref="uToast" />
		<common :tips='commonTips'></common>
		<u-form class="quick-reply" :model="form" ref="uForm">
			<u-form-item label="标题" label-position="top" prop="title">
				<u-input placeholder="请输入快捷回复标题" v-model="form.title" />
			</u-form-item>
			<u-form-item label="内容" label-position="top" prop="content">
				<u-input type="textarea" maxlength="-1" placeholder="请输入快捷回复内容,此处不支持富文本,若需富文本支持请上PC端" v-model="form.content" />
			</u-form-item>
			<u-form-item :border-bottom="false" label="状态">
				<u-switch active-value="1" inactive-value="0" slot="right" v-model="form.status"></u-switch>
			</u-form-item>
			<u-button class="quick-reply-button" :loading="submitButtonStatus" :disabled="submitButtonStatus" type="primary" @click="submit">提交</u-button>
		</u-form>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				id: 0,
				form: {
					title: '',
					content: '',
					status: true
				},
				rules: {
					title: [{
						required: true,
						message: '请输入标题',
						trigger: 'change'
					}],
					content: [{
						required: true,
						message: '请输入标题',
						trigger: 'change'
					}]
				},
				submitButtonStatus: false,
				commonTips: ''
			}
		},
		onLoad: function(data) {
			this.id = data.id ? data.id:0
			var title = (data.type == 'add') ? '添加':'编辑'
			uni.setNavigationBarTitle({
			    title: title + '快捷回复'
			});
			
			if (this.id) {
				this.ws.pageFun(this.pageDataLoad, this);
			}
		},
		onShow:function(){
			this.ws.checkNetwork(this)
		},
		onReady() {
			this.$refs.uForm.setRules(this.rules);
		},
		methods: {
			pageDataLoad: function () {
				let message = {
					c: 'Message',
					a: 'fastReply',
					data: {
						method: 'edit',
						id: this.id,
						platform: 'uni'
					}
				}
				this.ws.send(message);
			},
			submit: function() {
				var that = this
				this.$refs.uForm.validate(valid => {
					if (valid) {
						that.submitButtonStatus = true
						that.ws.pageFun(function() {
							that.form.status = that.form.status ? 1:0;
							let message = {
								c: 'Message',
								a: 'fastReply',
								data: {
									method: 'update',
									page: 1,
									platform: 'uni',
									data: {
										id: that.form.id ? that.form.id:0,
										title: that.form.title,
										content: that.form.content,
										status: that.form.status
									}
								}
							}
							that.ws.send(message);
							that.ws.showMsgCallback = function() {
								setTimeout(function() {
									that.submitButtonStatus = false
									uni.navigateBack({
										delta: 1
									})
								}, 2000)
							}
						}, that)
					}
				});
			}
		}
	}
</script>

<style>
.quick-reply {
	display: block;
	width: 92vw;
	margin: 0 auto;
}
.quick-reply-button {
	width: 60vw;
	display: block;
	margin: 0 auto;
	margin-top: 200rpx;
}
</style>
