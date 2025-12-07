<template>
	<view>
		<u-toast ref="uToast" />
		<common :tips='commonTips'></common>
		<u-modal v-model="modelShow" :mask-close-able="true" :show-cancel-button="true" @confirm="modelConfirm" :content="modelContent"></u-modal>
		<u-action-sheet v-if="type == 'user'" :list="userStatusList" :safe-area-inset-bottom="true" @click="changeUserStatus" v-model="userStatusBool"></u-action-sheet>
		<view class="user-box">
			<image :src="info.avatar" class="user-avatar" mode="aspectFill"></image>
			<view class="user-right">
				<view class="user-right-item user-name">
					<text class="nickname-text">{{info.nickname}}</text>
				</view>
				<!-- <view class="user-right-item">{{info.bio}}</view> -->
				<view @click="userStatusBool = (id == 0 ? true:false)" :class="'user-status-' + info.status.value" class="user-right-item user-status"> • {{info.status.chinese}}</view>
			</view>
		</view>
		<u-cell-group v-if="type == 'user'" :border="false" class="user-info-box">
			<block v-for="(item,index) in detail" :key="index">
				<u-cell-item hover-class="none" :border-top="false" :border-bottom="false" :arrow="false" :title="item.title">
					<view slot="right-icon" class="user-info-value">{{item.value}}</view>
				</u-cell-item>
			</block>
		</u-cell-group>
		<view class="user-buttons">
			<u-button @click="userAction(item.action, item.data, item.type, item.opt)" v-for="(item,index) in buttons" :key="index" :type="item.btype">{{item.name}}</u-button>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				id: 0,
				type: '',
				userStatusBool: false,
				userStatusList: [{
					text: '繁忙',
					color: '#8a6d3b'
				}, {
					text: '离开',
					color: '#a94442'
				}, {
					text: '在线',
					color: '#3c763d'
				}],
				info: {
					status: {
						value: 0,
						chinese: '加载中'
					},
					avatar: '',
					nickname: '加载中',
					bio: '这个人很懒，什么也没写~'
				},
				detail: [],
				buttons: [],
				userinfo: [],
				modelShow: false,
				modelContent: '',
				commonTips: ''
			}
		},
		onLoad:function(query){
			this.id = query.id ? query.id:0
			this.type = query.type ? query.type:'user'
			this.userinfo = uni.getStorageSync('userinfo');// 防止需要在pageDataLoad使用到用户ID
			this.ws.pageFun(this.pageDataLoad, this);
		},
		onShow() {
			this.ws.checkNetwork(this)
			if (this.ws.pageRefresh.info) {
				this.ws.pageFun(this.pageDataLoad, this);
				this.ws.pageRefresh.info = false
			}
		},
		methods: {
			pageDataLoad: function () {
				this.ws.send({ c: 'Message', a: 'getInfo', data: {
					id: this.id
				}})
			},
			changeUserStatus: function (index) {
				var that = this
				that.ws.pageFun(function () {
					that.ws.send({ c: 'Message', a: 'csrChangeStatus', data: { 'status': (index + 1) } });
					let status = ['离线', '繁忙', '离开', '在线'];
					that.info.status = {
						chinese: status[index + 1],
						value: index + 1
					}
				}, that);
			},
			userAction: function (action, data, type, opt) {
				if(action == 'open-session') {
					if (!this.info.session_id) {
						uni.showModal({
							title: '温馨提示',
							content: '抱歉，您要打开的会话找不到啦~',
							showCancel: false
						})
						return ;
					}
					uni.redirectTo({
						url: '/pages/message/message?id=' + this.info.session_id
					})
				} else if(action == 'userinfo-opt') {
					if (opt == 'edit') {
						uni.navigateTo({
							url: '/pages/user/edit-info?id=' + this.id
						})
					}
				} else if (action == 'close') {
					uni.navigateBack({
						delta: 1
					})
				}
			},
			modelConfirm: function () {
				
			}
		}
	}
</script>

<style lang="scss">
page {
	background-color: #F8F8F8;
}
.user-box {
	display: flex;
	padding: 20rpx 4vw;
	align-items: center;
}
.user-avatar {
	height: 120rpx;
	width: 120rpx;
}
.collection-user {
	display: flex;
	align-items: center;
	justify-content: center;
}
.user-right {
	width: 70%;
	padding-left: 20rpx;
}
.user-right-item {
	display: block;
	line-height: 32rpx;
}
.user-name {
	line-height: 46rpx;
	font-size: 30rpx;
	overflow: hidden;
	text-overflow:ellipsis;
	white-space: nowrap;
}
.nickname-text {
	display: inline-flex;
	max-width: 46%;
	overflow: hidden;
	text-overflow:ellipsis;
	white-space: nowrap;
}
.user-status {
	color: #999999;
	line-height: 46rpx;
}
.user-info-box {
	display: block;
	width: 92vw;
	margin: 0 auto;
	padding-top: 10rpx;
}
.user-info-value {
	width: 480rpx;
	word-wrap: break-word;
	word-break: break-all;
	overflow: hidden;
}
.user-buttons {
	padding: 40rpx 0;
	display: flex;
	align-items: center;
	justify-content: space-around;
}
.leader-avatar-box {
	display: flex;
	align-items: center;
}
.leader-avatar {
	height: 80rpx;
	width: 80rpx;
	margin-right: 10rpx;
}
.im-data-none {
	display: block;
	line-height: 100rpx;
	text-align: center;
	font-size: 28rpx;
	color: #999999;
}
</style>
