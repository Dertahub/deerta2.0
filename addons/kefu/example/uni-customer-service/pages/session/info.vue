<template>
	<view>
		<u-toast ref="uToast" />
		<common :tips='commonTips'></common>
		<u-modal v-model="delModelShow" :mask-close-able="true" :show-cancel-button="true" @confirm="delConfirm" :content="delModelContent"></u-modal>
		<u-select title="转接给" mode="single-column" :list="transferSelectList" v-model="transferShow" @confirm="transferSelectConfirm"></u-select>
		<u-form :model="info" ref="uForm">
			<u-cell-group>
				<view @click="goToUserInfo()">
					<u-cell-item :title="info.nickname">
						<image class="user-avatar" slot="icon" :src="info.avatar" mode="aspectFill"></image>
					</u-cell-item>
				</view>
			</u-cell-group>

			<view class="u-m-t-20 form-item">
				<navigator :url="'/pages/session/trajectory?id=' + info.session_user">
					<u-cell-item title="查看轨迹" title-width="160">
						<u-icon slot="right" name="arrow-right" color="#999" size="28"></u-icon>
					</u-cell-item>
				</navigator>
				<u-cell-item @click="transfer" title="转接会话" title-width="160">
					<u-icon slot="right" name="arrow-right" color="#999" size="28"></u-icon>
				</u-cell-item>
			</view>
			
			<view @click="block" class="u-m-t-20 fff-bg-item color-yellow">
				<view class="form-button">{{info.blacklist ? '解除拉黑':'拉黑名单'}}</view>
			</view>
			<view @click="delSession" class="u-m-t-20 fff-bg-item color-red">
				<view class="form-button">移除会话</view>
			</view>
		</u-form>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				id: 0,
				info: {
					nickname: '加载中...'
				},
				groupChatMember: [],
				delModelShow: false,
				delModelContent: '',
				delModelType: 'del',
				transferShow: false,
				transferSelectList: [],
				commonTips: ''
			}
		},
		onLoad: function(query) {
			this.id = query.id ? query.id : 0
			this.ws.pageFun(this.pageDataLoad, this);
		},
		onShow:function(){
			this.ws.checkNetwork(this)
		},
		methods: {
			pageDataLoad: function() {
				var that = this
				let message = {
					c: 'Message',
					a: 'sessionSetting',
					data: {
						'session_id': that.id
					}
				}
				that.ws.send(message);
			},
			transferSelectConfirm: function (val) {
				var that = this
				that.ws.pageFun(() => {
					that.ws.send({ c: 'Message', a: 'actionSession', data: {
						action: 'transfer_done',
						csr: val[0].value,
						session_user: that.info.session_user,
						platform: 'uni'
					}})
				}, that)
			},
			transfer: function () {
				var that = this
				that.ws.pageFun(() => {
					that.ws.send({ c: 'Message', a: 'actionSession', data: {
						action: 'transfer',
						session_user: that.info.session_user,
						platform: 'uni'
					}})
				}, that)
			},
			delConfirm: function () {
				var that = this
				if (that.delModelType == 'del') {
					that.ws.pageFun(function(){
						let message = {
							c: 'Message',
							a: 'actionSession',
							data: {
								'action': 'del',
								'session_user': that.info.session_user
							}
						}
						that.ws.send(message);
						that.ws.showMsgCallback = function(){
							that.ws.pageRefresh.session = true
						}
					}, that)
				} else if (that.delModelType == 'block') {
					that.ws.pageFun(function(){
						let message = {
							c: 'Message',
							a: 'blacklist',
							data: {
								'session_user': that.info.session_user
							}
						}
						that.ws.send(message);
					}, that)
				}
			},
			infoSwitchChange: function (name) {
				var that = this
				if (name == 'top') {
					that.ws.pageFun(function(){
						that.ws.send({
							c: 'User',
							a: 'sessionOperation',
							data: {
								id: that.id,
								action: 'session-top',
								source: 'uni-app'
							}
						})
					}, that)
				} else if (name == 'top_contacts') {
					that.ws.pageFun(function(){
						var new_group = 'common'
						if (that.info.sessionUser.group == 'common') {
							new_group = 'all_friends'
						}
						that.ws.send({
							c: 'User',
							a: 'updateFriendInfo',
							data: {
								id: that.info.sessionUser.id,
								new_group: new_group,
								method: 'update_group',
								source: 'uni-app'
							}
						});
						that.info.top_contacts = (new_group == 'common') ? true:false;
					}, that)
				} else if (name == 'shield') {
					that.ws.pageFun(function(){
						that.ws.send({
							c: 'User',
							a: 'shieldSession',
							data: {
								session_id: that.id,
								method: that.info.shield ? 'shield':'relieve'
							}
						})
					}, that)
				} else if (name == 'block_messages') {
					that.ws.pageFun(function(){
						that.ws.send({
							c: 'User',
							a: 'sessionOperation',
							data: {
								id: that.id,
								action: 'session-block-groupmessage',
								source: 'uni-app'
							}
						})
					}, that)
				}
			},
			goToUserInfo: function () {
				var url = '/pages/user/info?id=' + this.info.id
				uni.navigateTo({
					url: url
				})
			},
			delSession: function () {
				this.delModelContent = '你确定要移除此会话吗？';
				this.delModelShow = true
				this.delModelType = 'del'
			},
			block: function () {
				this.delModelContent = '你确定要拉黑此用户吗？';
				this.delModelShow = true
				this.delModelType = 'block'
			}
		}
	}
</script>

<style lang="scss">
	page {
		background-color: #F8F8F8;
	}

	.user-avatar {
		height: 100rpx;
		width: 100rpx;
		margin-right: 20rpx;
		border-radius: 16rpx;
	}

	.form-item {
		background: #FFFFFF;
	}

	.fff-bg-item {
		background: #FFFFFF;
	}

	.form-button {
		height: 100rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 30rpx;
	}

	.greate-group-chat {
		display: flex;
		align-items: center;
		justify-content: center;
		background: #EBEBEB;
		height: 100rpx;
		width: 100rpx;
		margin-right: 20rpx;
		border-radius: 16rpx;
	}

	.color-blue {
		color: #6388fb;
	}

	.color-red {
		color: #f74c31;
	}
	
	.color-yellow {
		color: #ff9900;
	}

	.group-chat-users {
		padding-left: 20rpx;
		padding-bottom: 10rpx;
		display: flex;
		flex-wrap: wrap;
		width: 100vw;
	}

	.chat-user-item {
		width: 120rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-wrap: wrap;
		margin-top: 20rpx;
	}

	.chat-user-item .chat-user-avatar {
		height: 100rpx;
		width: 100rpx;
		border-radius: 16rpx;
	}

	.chat-user-avatar-plus {
		height: 100rpx;
		width: 100rpx;
		border-radius: 16rpx;
		background: #F4F5F6;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.chat-user-nickname {
		font-size: 26rpx;
		padding-top: 10rpx;
		width: 100rpx;
		display: block;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		text-align: center;
	}
</style>
