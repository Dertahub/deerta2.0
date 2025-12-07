<!-- 本页为登录示例文件，您可以通过此文件了解到如何将FastAdmin系统中的会员登录到客服系统 -->
<template>
	<view class="content">
		
		<!-- 线上环境建议至少使用短信验证码或图形验证码之一 -->
		<view class="login">
			<view class="tis">请输入FastAdmin会员账户进行登录,此登录页示例将会员登录到客服系统的功能,线上建议参考文档整合到已有项目使用客服系统</view>
			<form @submit="submit">
				<view class="form-item">
					<view class="form-title">账号</view>
					<input name="account" type="text" placeholder="请输入账号" />
				</view>
				<view class="form-item">
					<view class="form-title">密码</view>
					<input name="password" type="password" placeholder="请输入密码" />
				</view>
				<button type="primary" plain="true" :disabled="loginButtonStatus" :loading="loginButtonStatus" form-type="submit" class="form-submit-button">登录</button>
			</form>
		</view>
	</view>
</template>

<script>
	import Config from "../kefu/config.js"; // 本地配置数据
	export default {
		data() {
			return {
				loginButtonStatus: false
			}
		},
		onLoad() {
			
			// 如果有用户资料缓存，直接跳转到客服会话页面
			// 通过传递`token`参数，客服系统可直接识别到fastadmin会员
			var userinfo = uni.getStorageSync('userinfo')
			if (userinfo) {
				uni.redirectTo({
					url: '/pages/kefu/kefu?token=' + userinfo.token // 传递`会员token`参数跳转即可
				})
			}
		},
		methods: {
			submit: function(e) {
				var that = this
				that.loginButtonStatus = true
				var protocol = Config.https_switch ? 'https://' : 'http://';
				uni.request({
					url: protocol + Config.baseURL + '/api/user/login', // 请求文件位置`根目录/application/api/controller/User.php`
					data: e.detail.value,
					method: 'POST',
					header: {
						'content-type': 'application/json'
					},
					success: res => {
						that.loginButtonStatus = false
						if (res.data.code == 1) {
							uni.setStorageSync('userinfo', res.data.data.userinfo);
							uni.redirectTo({
								url: '/pages/kefu/kefu?token=' + res.data.data.userinfo.token
							})
						} else {
							uni.showModal({
								title: '温馨提示',
								content: res.data.msg,
								showCancel: false
							})
						}
					},
					fail: res => {
						that.loginButtonStatus = false
						uni.showModal({
							title: '温馨提示',
							content: '请求失败，请重试！',
							showCancel: false
						})
					}
				})
			}
		}
	}
</script>

<style>
page {
	background: #F8F8F8;
}
.content {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 100vw;
	height: 60vh;
}
.tis {
	padding: 20rpx 0;
	font-size: 26rpx;
	color: #181818;
}
.login {
	width: 88vw;
	margin: 0 auto;
	background: #fff;
	padding: 20rpx;
	border-radius: 16rpx;
	font-size: 30rpx;
}
.form-item {
	margin: 20rpx 0;
}
.form-item input {
	border: 1px solid #F8F8F8;
	padding: 10rpx;
	margin: 20rpx 0;
	border-radius: 16rpx;
}
.form-submit-button {
	height: 80rpx;
	font-size: 32rpx;
	line-height: 80rpx;
	width: 300rpx;
	margin: 20rpx auto;
	margin-top: 80rpx;
}
</style>
