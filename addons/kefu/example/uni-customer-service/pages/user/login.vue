<template>
	<view>
		<u-modal v-model="modelShow" :content="modelContent"></u-modal>
		<common :tips='commonTips'></common>
		<view class="user-avatar">
			<image :src="info.avatar" mode="widthFix"></image>
		</view>
		<u-form :model="form" label-width="120" label-position="top" class="login-form" ref="uForm">
			<u-form-item label="账　号" prop="username">
				<u-input v-model="form.username" placeholder="请输入账号" name="username" />
			</u-form-item>
			<u-form-item label="密　码" prop="password">
				<u-input v-model="form.password" :password-icon="true" placeholder="请输入密码" type="password" name="password" />
			</u-form-item>
			<u-form-item label="验证码" prop="captcha">
				<u-input placeholder="请输入验证码" v-model="form.captcha" type="number"></u-input>
				<image @click="downloadCaptcha" class="captcha-img" slot="right" :src="captchaPath" mode="widthFix"></image>
			</u-form-item>
			<u-button @click="submit" :custom-style="loginFormButtonStyle" shape="square" :hair-line="false" :ripple="true" :disabled="loginFormButtonDisabled" ripple-bg-color="rgba(45,211,232, 0.8)">
				登录
			</u-button>
		</u-form>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				form: {
					username: '',
					password: '',
					captcha: ''
				},
				rules: {
					username: [
						{
							required: true, 
							message: '请输入账号',
							trigger: ['change', 'blur']
						}
					],
					password: [
						{
							required: true,
							min: 6, 
							message: '请输入正确的密码', 
							trigger: ['change', 'blur']
						}
					],
					captcha: [
						{
							required: true,
							min: 4,
							message: '请输入正确的验证码',
							trigger: ['change', 'blur']
						}
					]
				},
				loginFormButtonStyle: {
					backgroundImage: "linear-gradient(to bottom right, #34E4E8, #1FAEE8)",
					border: 'none',
					color: '#ffffff',
					outline: 'none',
					marginTop: '80rpx',
				},
				loginFormButtonDisabled: false,
				modelShow: false,
				modelContent: '',
				info: {
					avatar: '../../static/img/avatar.png'
				},
				captchaPath: '',
				commonTips: ''
			}
		},
		onLoad: function () {
			let useravatar = uni.getStorageSync('useravatar');
			if (useravatar) {
				this.info.avatar = this.ws.imgUrl(useravatar)
			}
			this.downloadCaptcha()
		},
		onReady() {
			this.$refs.uForm.setRules(this.rules);
		},
		methods: {
			tab: function (index) {
				this.tabIndex = index
			},
			downloadCaptcha: function() {
				var that = this
				that.form.captcha = ''
				that.$u.post('/api/kefu/captchaPre', {}).then(res => {
					that.ws.captchaId = res.data.captcha_id;
					that.captchaPath = this.ws.buildUrl('default') + '/api/kefu/captcha?captcha_id=' + res.data.captcha_id;
				}).catch(res => {
					that.$u.toast('验证码请求失败，请重试！');
				})
			},
			submit: function() {
				var that = this
				that.$refs.uForm.validate(valid => {
					if (valid) {
						that.loginFormButtonDisabled = true
						that.form.captcha_id = that.ws.captchaId
						that.$u.post('/api/kefu/login', that.form).then(res => {
							if (res.code == 1) {
								uni.setStorageSync('userinfo', res.data.userinfo);
								uni.setStorageSync('useravatar', res.data.userinfo.avatar);
								that.ws.init(res.data.userinfo.token)
								uni.reLaunch({
									url: '/pages/session/session'
								})
							} else {
								that.modelContent = res.msg
								that.modelShow = true
								that.downloadCaptcha()
							}
							that.loginFormButtonDisabled = false
						}).catch(res => {
							that.downloadCaptcha()
							that.loginFormButtonDisabled = false
							that.$u.toast('请求失败，请重试！');
						})
					}
				});
			}
		}
	}
</script>

<style>
page {
	background-color: #fff;
}
.user-avatar {
	width: 100vw;
	display: flex;
	align-items: center;
	justify-content: center;
	padding-top: 60rpx;
}
.user-avatar image {
	width: 180rpx;
	height: auto;
}
.login-form {
	display: block;
	width: 78vw;
	padding-top: 40rpx;
	margin: 0 auto;
}
.login-footer-box {
	position: fixed;
	bottom: 20rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	width: 100vw;
}
.login-footer-box-left {
	text-decoration: underline;
	font-size: 28rpx;
	color: #6388fb;
}
.captcha-img {
	width: 200rpx;
	max-height: 100rpx;
}
</style>
