<template>
	<view>
		<!-- title=反馈&举报&id=224&type=user -->
		<!-- 举报时：id为会话ID，type当举报时可固定为user -->
		<!-- 反馈时：仅需传递title=反馈 -->
		<u-toast ref="uToast" />
		<common :tips='commonTips'></common>
		<u-form class="quick-reply" :model="form" ref="uForm">
			<u-form-item :label="formTitle + '详情'" label-position="top" prop="describe">
				<u-input type="textarea" :placeholder="describePlaceholder" v-model="form.describe" />
			</u-form-item>
			<u-form-item label="联系方式" label-position="top" prop="mobile">
				<u-input placeholder="请输入联系方式" v-model="form.mobile" />
			</u-form-item>
			<u-form-item label="图片证据" label-position="top">
				<view>
					<u-upload @on-change="avatarOnChange" :form-data="uploadFormData" ref="uUpload" :size-type="['compressed']" name="file" :max-count="4" :show-tips="false" :action="uploadAction" :file-list="fileList" ></u-upload>
				</view>
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
				type: 'user',
				userToken: '',
				form: {
					describe: '',
					mobile: '',
					reportimage: ''
				},
				formTitle: '反馈',
				uploadAction: '',
				uploadFormData: new Object(),
				fileList: [],
				rules: {
					describe: [{
						required: true,
						message: '请输入详情',
						trigger: ['change', 'blur']
					}],
					mobile: [{
						required: true,
						message: '请输入联系方式',
						trigger: ['change', 'blur']
					}]
				},
				describePlaceholder: '',
				submitButtonStatus: false,
				commonTips: ''
			}
		},
		onLoad(data) {
			this.id = data.id ? data.id:0
			this.type = data.type ? data.type:'feedback'
			let userinfo = uni.getStorageSync('userinfo');
			this.userToken = userinfo.token
			
			this.formTitle = data.title ? data.title:this.formTitle
			this.describePlaceholder = '请输入' + this.formTitle + '详情'
			uni.setNavigationBarTitle({
			    title: this.formTitle
			});
			
			this.ws.pageFun(this.pageDataLoad, this)
		},
		onReady() {
			this.$refs.uForm.setRules(this.rules);
		},
		onShow:function(){
			this.ws.checkNetwork(this)
		},
		methods: {
			pageDataLoad: function () {
				var that = this				
				if (that.id) {
					let message = {
						c: 'ImBase',
						a: 'report',
						data: {
							session_id: that.id,
							type: 'user',
							method: 'get'
						}
					}
					this.ws.send(message);
				}
				
				that.ws.send({
					c: 'ImBase',
					a: 'getUploadMultipart'
				});
				
				that.uploadAction = this.ws.buildUrl('upload', that.userToken)
			},
			avatarOnChange: function (res, index, lists) {
				res = JSON.parse(res.data);
				if (res.code == 0) {
					
					this.ws.pageFun(() => {
						this.ws.send({
							c: 'ImBase',
							a: 'getUploadMultipart'
						});
					}, this)
					
					uni.showModal({
						title: '温馨提示',
						content: res.msg,
						showCancel: false
					})
					
					this.$refs.uUpload.remove(index);
					return false;
				}
			},
			submit: function () {
				var that = this
				this.$refs.uForm.validate(valid => {
					if (valid) {
						var files = '';
						for (let f in that.$refs.uUpload.lists) {
							if (that.$refs.uUpload.lists[f].progress == 100) {
								files += that.ws.imgUrl(that.$refs.uUpload.lists[f].response.data.fullurl) + ','
							}
						}
						if (!files) {
							that.$refs.uToast.show({
								title: '请上传证据图片~',
								type: 'error'
							})
							return;
						}
						that.submitButtonStatus = true
						that.form.type = that.type
						that.form.reportimage = files
						that.form.session_id = that.id
						that.form.method = 'post'
						
						that.ws.pageFun(function() {
							let message = { c: 'ImBase', a: 'report', data: that.form }
							that.ws.send(message);
							that.ws.showMsgCallback = function () {
								that.form.describe = ''
								that.form.mobile = ''
								that.$refs.uUpload.clear();
								setTimeout(function(){
									that.submitButtonStatus = false
									uni.navigateBack({
										delta: 1
									})
								}, 2000)
							}
						}, that)
					}
				})
			}
		}
	}
</script>

<style>
page {
	background: #FFFFFF;
}
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
