<template>
	<view>
		<u-toast ref="uToast" />
		<common :tips='commonTips'></common>
		<u-modal v-model="logoutShow" :show-cancel-button="true" @confirm="logout()" confirm-text="注销" :content="logoutContent"></u-modal>
		<navigator url="/pages/user/info">
			<view class="u-flex user-box u-p-l-30 u-p-r-20 u-p-b-30 u-p-t-30">
				<view class="u-m-r-10">
					<u-avatar v-if="info.avatar" :src="info.avatar" size="140"></u-avatar>
				</view>
				<view class="u-flex-1">
					<view class="u-font-18 u-p-b-20">{{info.nickname}}</view>
					<view class="u-font-14 u-tips-color">账号：{{info.id}}</view>
				</view>
				<view class="u-m-l-10 u-p-10 user-status" :class="'user-status-' + info.status.value">•<text>{{info.status.chinese}}</text></view>
				<view class="u-m-l-10 u-p-10">
					<u-icon name="arrow-right" color="#969799" size="28"></u-icon>
				</view>
			</view>
		</navigator>
		
		<view class="u-m-t-20">
			<u-cell-group :border="false">
				<navigator url="/pages/user/quick-reply">
					<u-cell-item :border-top="false" icon="zhuanfa" title="快捷回复"></u-cell-item>
				</navigator>
				<navigator url="/pages/user/blacklist">
					<u-cell-item :border-bottom="false" icon="minus-circle" title="黑名单管理"></u-cell-item>
				</navigator>
			</u-cell-group>
		</view>
		
		<view class="u-m-t-20">
			<u-cell-group :border="false">
				<u-cell-item @click="logoutConfirm" :border-bottom="false" :border-top="false" icon="close-circle" title="注销登录"></u-cell-item>
			</u-cell-group>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				info: {},
				logoutShow: false,
				commonTips: '',
				logoutContent: '确实要注销登录吗?'
			}
		},
		onLoad() {
			this.info = uni.getStorageSync('userinfo');// 防止需要在pageDataLoad使用到用户ID
			this.info.avatar = this.ws.imgUrl(this.info.avatar)
			this.info.status = {chinese: '加载中', value: 0}
			
			// #ifdef APP-PLUS
			this.logoutContent = '注销后将无法接受离线消息推送,确实要注销登录吗?'
			// #endif
		},
		onShow:function(){
			this.ws.checkNetwork(this)
			this.ws.pageFun(this.pageDataLoad, this);
		},
		onPullDownRefresh: function () {
			this.ws.pageFun(this.pageDataLoad, this);
			this.ws.onMessageCallBack.set('user-info', () => {
				this.$refs.uToast.show({
					title: '刷新成功~',
					type: 'success'
				})
				uni.stopPullDownRefresh()
			});
		},
		methods: {
			pageDataLoad: function () {
				this.ws.send({ c: 'Message', a: 'getInfo', data: {
					id: 0
				}})
			},
			logoutConfirm: function () {
				this.logoutShow = true
			},
			logout: function () {
				var that = this
				// #ifdef APP-PLUS
				if (!this.ws.socketOpen || parseInt(that.ws.initializeData.config.uni_push_switch) == 0) {
					that.ws.logout()
				} else {
					that.ws.pushCid('logout')
				}
				// #endif
				
				// #ifndef APP-PLUS
				that.ws.logout()
				// #endif
			}
		}
	}
</script>

<style lang="scss">
page{
	background-color: #ededed;
}
.user-box, .to-do {
	background-color: #FFFFFF;
}
.user-status {
	font-size: 30rpx;
}
.user-status text {
	padding-left: 10rpx;
}
.to-do u-cell-item u-icon {
	margin-right: 10rpx;
}
.to-do-title {
	display: flex;
	align-items: center;
}
.to-do-icon {
	width: 28rpx;
	margin: 0 12rpx 0 6rpx;
}
</style>
