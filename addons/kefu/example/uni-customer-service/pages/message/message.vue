<template>
	<view>
		<u-toast ref="uToast" />
		<common :tips='commonTips' :navbar-height="navbarHeight + statusBarHeight"></common>
		<u-popup width="94%" border-radius="16" mode="center" :closeable="true" v-model="LinkPopupShow">
			<view class="link-popup-box">
				<view class="link-popup-title">你将要访问的网址是：</view>
				<view class="link-popup-url">{{linkPopupUrl}}</view>
				<view class="link-popup-tis">请确认是否继续访问，并注意您的账户和财产安全！</view>
				<view class="link-popup-buttons">
					<u-button :custom-style="{padding: '0 2rpx'}" class="link-popup-button-item" hover-class="none" type="primary" size="medium" plain>
						<u-link :href="linkPopupUrl"><text class="link-popup-url-button">访问</text></u-link>
					</u-button>
					<u-button @click="LinkPopupShow = false" class="link-popup-button-item" type="default" size="medium">取消</u-button>
				</view>
			</view>
		</u-popup>
		<u-navbar :background="navBackground">
			<view class="navbar-title">
				<view class="title-content">{{info.nickname}}</view>
				<view class="title-other" v-if="info.status" :class="'user-status-' + info.status.value">{{info.status.chinese}}</view>
			</view>
			<view @click="sessionMenu" class="menu-wrap" slot="right">
				<u-icon name="grid-fill" color="#000000" size="38"></u-icon>
			</view>
		</u-navbar>
		
		<!-- mask 目前只用于长按消息菜单显示时 -->
		<view v-if="maskShow" @click="maskClick" class="mask"></view>
		
		<view v-if="messageLongpressAction.length" :style="messageLongpressActionStyle" class="message-longpress-action">
			<view v-for="(item, index) in messageLongpressAction" :key="index" @click="messageLongpressActionClick(index)" class="longpress-action-item">{{item.name}}</view>
			<view class="longpress-action-pin" :style="messageLongpressActionPinStyle">
				<u-icon name="arrow-down-fill" color="#262626" size="30"></u-icon>
			</view>
		</view>
		
		<!-- 聊天记录-start -->
		<scroll-view
		@scroll="scrollWrapper"
		@scrolltolower="wrapperScrolltoupper"
		@click="clickWrapper"
		class="wrapper"
		id="wrapper"
		:scroll-y="true"
		:scroll-top="wrapperScrollTop"
		:style="{height: 'calc(100vh - ' + (navbarHeight + statusBarHeight) + 'px - ' + writeHeight + 'px)'}"
		>
			<view class="message-item-wrap">
				<block v-if="messageList.length" v-for="(item, index) in messageList" :key="index">
					<block v-for="(message, m) in item.data" :key="message.id">
						<block v-if="message.message_type == 3">
							<view class="status">
								<text>{{message.message}}</text>
							</view>
						</block>
						<view v-if="message.message_type != 3" class="message-item" :class="message.sender">
							<!-- <image @click="clickAvatar(info.type, message.sender)" class="message-avatar" :src="(message.sender == 'you') ? info.sessionUser.avatar:info.user.avatar"></image> -->
							<view class="message-content-box">
								<!-- <view v-if="message.pushUser && message.pushUser.nickname" class="chat-record-nickname">{{message.pushUser.nickname}}</view> -->
								<view :id="'message-' + message.id" class="message-content" :class="message.pushUser && message.pushUser.nickname ? '':'hide-nickname'">
									<message :value="message"></message>
								</view>
								<view v-if="message.sender == 'me' && message.status <= 1" class="im-message-status" :class="(message.status == 0 ? '':' kf-text-grey')">{{message.status == 0 ? '未读':'已读'}}</view>
								<view v-if="message.sender == 'me' && message.status == 3" class="im-message-status kf-text-red">失败</view>
							</view>
						</view>
					</block>
					<view class="status">
						<text>{{item.datetime}}</text>
					</view>
				</block>
				<block v-if="!messageList.length">
					<view class="status">
						<text>暂时没有聊天记录...</text>
					</view>
				</block>
			</view>
		</scroll-view>
		<!-- 聊天记录-end -->
		
		<!-- 消息输入-start -->
		<view class="im-write" :style="{bottom: writeBottom + 'px'}">
			<view class="session-user-input-status-box">
				<view v-if="sessionUserInputStatus" class="session-user-input-status">对方正在输入...</view>
			</view>
			<view class="write-textarea">
				<textarea :adjust-position="false" :show-confirm-bar="false" :fixed="true" :focus="imMessageFocusBool"
				 :auto-height="true" :cursor-spacing="14" maxlength="-1" @blur="imMessageBlur" @input="imMessageInput"
				 @focus="imMessageFocus" :confirm-type="sendButtonType" @confirm="sendButtonConfirm" v-model="imMessage" class="im-message"></textarea>
			</view>
			<view class="write-right" :style="{flex:showSendButton ? 3:2}">
				<image class="toolbar-icon emoji" src="/static/icon/emoji.png" @click="clickTool('emoji')" mode="widthFix"></image>
				<button class="send-btn" @click="sendMessage(imMessage, 0)" hover-class="send-btn-hover" v-if="showSendButton">发送</button>
				<image class="toolbar-icon more" src="/static/icon/more.png" @click="clickTool('more')" mode="widthFix" v-if="!showSendButton"></image>
			</view>
		</view>
		<!-- 消息输入-end -->
		
		<view v-if="showTool" class="footer-tool">
			<!-- 表情-start -->
			<view v-if="showTool == 'emoji'">
				<view v-for="(item, index) in emoji" :key="index" class="emoji-img" hover-class="emoji-img-hover">
					<image :src="item.src" @click="selectEmoji(index)"></image>
				</view>
			</view>
			<!-- 表情-end -->
			<!-- 快捷回复-start -->
			<view v-if="showTool == 'reply'" class="reply-list">
				<view v-for="(item, index) in fastReply" :key="index" @click="sendMessage(item.content, 0)" class="reply-item">{{item.title}}</view>
			</view>
			<!-- 快捷回复-end -->
			<!-- 更多-start -->
			<view v-if="showTool == 'more'" class="toolbar">
				<view @click="clickTool('reply')" class="toolbar-item" hover-class="toolbar-item-hover">
					<image src="/static/icon/reply.png"></image>
					<view>快捷回复</view>
				</view>
				<view @click="clickMoreTool('image')" class="toolbar-item" hover-class="toolbar-item-hover">
					<image src="/static/icon/image.png"></image>
					<view>发送图片</view>
				</view>
				<view @click="clickMoreTool('attachment')" class="toolbar-item" hover-class="toolbar-item-hover">
					<!-- #ifdef H5 -->
					<image src="/static/icon/attachment.png"></image>
					<view>发送文件</view>
					<!-- #endif -->
					<!-- #ifndef H5-->
					<image src="/static/icon/video.png"></image>
					<view>发送视频</view>
					<!-- #endif -->
				</view>
			</view>
			<!-- 更多-end -->
		</view>
	</view>
</template>

<script>
	var systemInfo = uni.getSystemInfoSync(), defaultWriteHeight = 46
	export default {
		data() {
			return {
				id: 0,
				loadRecordsData: null,
				info: {
					nickname: '加载中...',
					status: {
						chinese: '未知',
						value: 0
					}
				},
				userId: 0,
				navBackground: {
					backgroundColor: '#F5F6F7'
				},
				emoji:[],
				navbarHeight: 0,
				writeHeight: defaultWriteHeight,
				writeBottom: 0,
				wrapperScrollTop: 0,
				wrapperScrollTopOld: 2,
				statusBarHeight: systemInfo.statusBarHeight,
				messageList: [],
				imMessageFocusBool: false,
				showSendButton: false,
				showTool: false,
				imMessage: '',
				sessionUserInputStatus: false,
				LinkPopupShow: false,
				linkPopupUrl: '',
				maskShow: false,
				messageLongpressAction: [],
				messageLongpressActionStyle: "",
				messageLongpressActionPinStyle: "",
				sendButtonType: 'none',
				fastReply: {},
				uploadFormData: new Object(),
				userPlatform: '',
				commonTips: '',
				unexpectedRecords: 0,
			}
		},
		onLoad: function (query) {
			
			// #ifdef APP-PLUS || H5
			this.navbarHeight = this.navbarHeight ? this.navbarHeight : 44;
			// #endif
			
			// #ifdef MP
			if (systemInfo.platform == 'ios') {
				var platformHeight = 44;
				defaultWriteHeight = 60;
			} else {
				var platformHeight = 48;
				defaultWriteHeight = 46;
			}
			this.navbarHeight = this.navbarHeight ? this.navbarHeight : platformHeight;
			this.writeHeight = defaultWriteHeight
			// #endif
			
			this.id = query.id ? query.id : 0
			this.ws.pageFun(this.pageDataLoad, this);
		},
		onShow:function(){
			this.ws.checkNetwork(this)
			if (this.ws.pageRefresh.sessionInfo) {
				this.ws.pageFun(this.pageDataLoad, this);
				this.ws.pageRefresh.sessionInfo = false
			}
		},
		methods: {
			sendButtonConfirm: function () {
				if (this.sendButtonType == 'send') {
					this.sendMessage(this.imMessage, 0)
				}
			},
			sendMessage: function (message, type = 0) {
				var that = this
				if (!message) {
					that.$refs.uToast.show({
						title: '请输入消息内容~',
						type: 'error'
					})
					return false;
				}
				
				if (type == 0) {
					// 处理表情
					var reg = /\[(.+?)\]/g; // [] 中括号
					var regMatch = message.match(reg);
					if (regMatch) {
						for (let i in regMatch) {
							var emojiItem = that.findEmoji(regMatch[i]);
							message = message.replace(emojiItem.title, '<img draggable="false" class="emoji" src="' + emojiItem.src + '" />');
						}
					}
				}
				
				var messageId = new Date().getTime() + that.info.id + Math.floor(Math.random() * 10000); // 临时消息ID
				that.ws.pageFun(res => {
					that.ws.send({
						c: 'Message',
						a: 'sendMessage',
						data: {
							message: message,
							message_type: type,
							session_id: that.id,
							modulename: 'admin',
							message_id: messageId,
							token: that.ws.initializeData.tokens ? that.ws.initializeData.tokens.kefu_token:''
						}
					});
				}, that)
				
				var messageObj = {
					id: messageId,
					status: {
						status: '',
						statusClass: ''
					},
					sender: 'me',
					message: (type == 1 || type == 2) ? that.ws.imgUrl(message) : message,
					message_type: type
				}
				that.imMessage = ''
				that.imMessageChange()
				
				if (that.messageList[0]) {
					that.messageList[0].data.unshift(that.ws.formatMessage(messageObj));
				} else {
					that.messageList.unshift({
						datetime: '刚刚',
						data: [that.ws.formatMessage(messageObj)]
					});
				}
				
				if (type == 1) {
					message = '[图片]';
				} else if (type == 2) {
					message = '[链接]';
				} else if (type == 4 || type == 5) {
					message = '[卡片消息]';
				} else {
					message = message.replace(/<img(.*?)src=(.*?)>/g, "[图片]");
				}
				
				that.ws.sessionShow.push(function(mThat) {
					that.ws.imSession({
						id: that.id,
						last_time: that.getHoursMinutes(),
						last_message: message,
						unreadMessagesNumber: 0
					}, mThat)
				});
				that.inputStatus(false)
				that.scrollIntoFooter()
				that.unexpectedRecords++
			},
			findEmoji: function(emojiCode) {
				for (let i in this.emoji) {
					if (this.emoji[i].title == emojiCode) {
						return this.emoji[i];
					}
				}
				return false;
			},
			maskClick: function () {
				this.maskShow = false
				this.messageLongpressAction = []
			},
			messageLongpressActionClick: function (index) {
				var that = this, messageId = that.messageLongpressAction[index].id, action = that.messageLongpressAction[index].action
				if (action == 'forward') {
					// 转发
					that.maskClick()
					uni.navigateTo({
						url: '/pages/pick-user/pick-user?action=message-forward&forward_type=message&message_id=' + messageId + '&sessionId=' + that.id
					})
				} else {
					that.ws.pageFun(() => {
						that.ws.send({
							c: 'Message',
							a: 'messageOperation',
							data: {
								id: messageId,
								action: 'message-' + action,
								source: 'uni-app'
							}
						});
					}, that)
				}
			},
			longpressMessage: function(e) {
				var that= this, action = []
				let message = uni.createSelectorQuery().select('#message-' + e.id);
				message.fields({
					rect: true,
					size: true
				}, data => {
					// 各种消息类型一个一个设置，确保菜单顺序
					if (e.type == 'default') {
						action = [
							{id: e.id, name: '复制', action: 'copy'},
							{id: e.id, name: '转发', action: 'forward'},
							{id: e.id, name: '收藏', action: 'collection'},
							{id: e.id, name: '待办', action: 'to-do'}
						]
					} else if (e.type == 'file' || e.type == 'link') {
						action = [
							{id: e.id, name: '复制', action: 'copy'},
							{id: e.id, name: '转发', action: 'forward'},
							{id: e.id, name: '收藏', action: 'collection'}
						]
					} else {
						action = [
							{id: e.id, name: '转发', action: 'forward'},
							{id: e.id, name: '收藏', action: 'collection'}
						]
					}
					
					if (that.info.type == 'single') {
						action.push({id: e.id, name: '删除', action: 'delete'})
					}
					
					if (e.sender == 'me') {
						let actionWidth = action.length * 40, missWidth = (actionWidth - data.width) + 20, left = (missWidth > 0) ? (data.left - missWidth):data.left
						that.messageLongpressActionStyle = 'top: ' + (data.top - 46) + 'px;left: ' + left + 'px';
					} else {
						that.messageLongpressActionStyle = 'top: ' + (data.top - 46) + 'px;left: ' + data.left + 'px';
					}
					that.maskShow = true
					that.messageLongpressAction = action
					that.messageLongpressActionPinStyle = 'top: ' + (data.top - 16) + 'px;left: ' + (data.left + 6) + 'px';
				}).exec()
			},
			getHoursMinutes: function() {
				var dateObj = new Date();
				var hours = dateObj.getHours();
				hours = hours < 10 ? '0' + hours : hours;
				var minutes = dateObj.getMinutes();
				minutes = minutes < 10 ? '0' + minutes : minutes;
				return hours + ':' + minutes;
			},
			selectEmoji: function(idx) {
				this.imMessage += this.emoji[idx].title
				this.imMessageChange();
				this.imMessageFocusBool = true
			},
			sessionMenu: function () {
				uni.navigateTo({
					url: '/pages/session/info?id=' + this.id
				})
			},
			clickAvatar: function (type, id) {
				uni.navigateTo({
					url: '/pages/user/info'
				})
			},
			pageDataLoad: function () {
				var that = this
				let message = {
					c: 'Message',
					a: 'chatRecord',
					data: {
						'session_id': that.id,
						'page': 1
					}
				}
				that.ws.send(message);
				that.userPlatform = that.ws.userPlatform;
				if (!that.emoji.length) {
					that.ws.getEmoji();
					that.fastReply = that.ws.initializeData.fastReply;
				}
			},
			scrollWrapper: function(e) {
				this.wrapperScrollTopOld = e.detail.scrollTop
				this.maskShow && this.maskClick()
			},
			wrapperScrolltoupper: function () {
				var that = this
				if (this.loadRecordsData == 'done') {
					return ;
				}
				
				// 加载历史聊天记录
				var load_message = {
					c: 'Message',
					a: 'chatRecord',
					data: {
						session_id: this.id,
						page: this.loadRecordsData,
						unexpected_records: that.unexpectedRecords,
					}
				};
				
				that.ws.pageFun(function(){
					that.ws.send(load_message);
					that.loadRecordsData = 'done'
				}, that)
			},
			clickWrapper: function () {
				this.clickTool(false)
			},
			clickTool: function (value) {
				if (!value || (value == this.showTool)) {
					this.showTool = false;
					this.writeBottom = 0;
					this.writeHeight = defaultWriteHeight;
				} else {
					this.showTool = value;
					this.writeBottom = 170;
					this.writeHeight = 216;
					this.scrollIntoFooter();
				}
			},
			clickMoreTool: function (name) {
				var that = this
				if (name == 'image') {
					that.ws.pageFun(() => {
						that.ws.send({
							c: 'Message',
							a: 'getUploadMultipart'
						});
					}, that)
					uni.chooseImage({
					    count: 1,
					    sizeType: ['original', 'compressed'],
					    sourceType: ['album', 'camera'],
					    success: function (res) {
							that.upload(res.tempFiles[0], function (res, file) {
								that.sendMessage(that.ws.imgUrl(res.data.fullurl || res.data.url), 1);
							});
						},
						fail: () => {}
					});
				} else if (name == 'attachment') {
					that.ws.pageFun(() => {
						that.ws.send({
							c: 'Message',
							a: 'getUploadMultipart'
						});
					}, that)
					var uploadFileCallBack = function (res, file) {
						let fileName = res.data.url.split('.');
						let fileSuffix = fileName[fileName.length - 1];
						let imgSuffix = ['png', 'jpg', 'gif', 'jpeg', 'bmp'];
						let audioSuffix = ['mp3', 'mpeg', 'wav', 'acc'];
						let videoSuffix = ['mp4', 'ogg', 'webm'];
						
						if (imgSuffix.includes(fileSuffix)) {
							that.sendMessage(that.ws.imgUrl(res.data.fullurl || res.data.url), 1);
						} else {
							let fileSize = file.size ? (file.size / 1024).toFixed(2):0
							if (fileSize > 0) {
								fileSize = fileSize > 1024 ? (fileSize / 1024).toFixed(2) + 'Mb' : fileSize + 'Kb'
							}
							that.sendMessage(that.ws.imgUrl(res.data.fullurl || res.data.url), 2);
						}
					}
					
					// #ifdef H5
					uni.chooseFile({
						count: 1,
						success (res) {
							that.upload(res.tempFiles[0], uploadFileCallBack);
						},
						fail: () => {}
					})
					// #endif
					
					// #ifndef H5
					uni.chooseVideo({
						count: 1,
						sourceType: ['camera', 'album'],
						success: function (res) {
							let path = {
								path: res.tempFilePath,
								size: res.size ? res.size:0
							}
							that.upload(path, uploadFileCallBack);
						},
						fail() {}
					});
					// #endif
				} else if (name == 'collection') {
					uni.navigateTo({
						url: '/pages/center/collection?action=send'
					})
				}
			},
			upload: function (path, callBack) {
				var that = this
				uni.showLoading({
					title: '正在上传...'
				})
				
				const uploadTask = uni.uploadFile({
					url: that.ws.buildUrl('upload', that.ws.initializeData.tokens.kefu_token),
					// #ifdef APP-PLUS || H5
					file: path,
					// #endif
					filePath: path.path,
					name: 'file',
					formData: that.uploadFormData,
					success: (uploadFileRes) => {
						uni.hideLoading()
						let res = JSON.parse(uploadFileRes.data);
						if (res.code == 1) {
							callBack(res, path)
						} else {
							uni.showModal({
								title: '温馨提示',
								content: res.msg,
								showCancel: false
							})
						}
					},
					fail: res => {
						uni.hideLoading()
						uni.showModal({
							title: '温馨提示',
							content: '上传失败,请重试~',
							showCancel: false
						})
					},
					complete: res => {
						uni.hideLoading()
					}
				});
				
				// #ifndef APP-PLUS
				uploadTask.onProgressUpdate((res) => {
					uni.showLoading({
						title: res.progress + '%'
					})
				});
				// #endif
			},
			imMessageBlur: function () {
				this.imMessageFocusBool = false
				if (!this.showTool) {
					this.writeBottom = 0;
					this.writeHeight = defaultWriteHeight;
				}
				this.inputStatus(false)
			},
			imMessageFocus: function (e) {
				this.clickTool(false)
				
				let writeHeight = () => {
					this.writeBottom = e.detail.height || 0
					this.writeHeight = (parseInt(this.writeBottom) + defaultWriteHeight);
				}
				if (this.ws.userPlatform == 'ios') {
					// #ifdef APP-PLUS
					uni.onKeyboardHeightChange(res => {
						this.writeBottom = res.height || e.detail.height || 0
						this.writeHeight = (parseInt(this.writeBottom) + defaultWriteHeight);
						uni.offKeyboardHeightChange(() => {})
					})
					// #endif
					
					// #ifndef APP-PLUS
					writeHeight()
					// #endif
				} else {
					writeHeight()
				}
				this.scrollIntoFooter()
			},
			imMessageInput: function (e) {
				this.imMessage = e.detail.value;
				this.imMessageChange()
				this.inputStatus()
			},
			// 显示/隐藏发送按钮-调整消息记录框高度
			imMessageChange: function() {
				var that = this
				that.showSendButton = (that.imMessage == '') ? false : true;
			},
			inputStatus: function (type = true) {
				var that = this, input_status_display = parseInt(this.ws.initializeData.config.input_status_display)
				if (input_status_display > 0) {
					that.ws.pageFun(function(){
						that.ws.send({
							c: 'Message',
							a: 'messageInput',
							data: {
								'session_id': that.id,
								'session_user': that.info.session_user,
								'type': type ? 'input':'blur'
							}
						});
					}, that)
				}
			},
			scrollIntoFooter: function () {
				var that = this
				that.wrapperScrollTop = that.wrapperScrollTopOld
				that.$nextTick(() => {
					that.wrapperScrollTop = 0
				})
			}
		}
	}
</script>

<style lang="scss">
page {
	background-color: #F5F6F7;
	overflow: hidden;
}
.menu-wrap {
	display: flex;
	align-items: center;
	padding: 12rpx;
	border-radius: 50%;
	margin-right: 20rpx;
	margin-top: -10rpx;
}
.navbar-title {
	display: flex;
	align-items: center;
}
.navbar-title .title-other {
	font-size: 22rpx;
	margin-left: 10rpx;
}
.title-content {
	display: block;
	overflow: hidden;
	text-overflow:ellipsis;
	white-space: nowrap;
	max-width: 300rpx;
}
.wrapper {
	transform: scaleY(-1);
	position: relative;
	overflow: hidden;
	box-sizing: border-box;
}
.message-item-wrap {
	min-height: 100%;
	display: flex;
	flex-direction: column;
	justify-content: flex-end;
}
.wrapper .status {
	transform: scaleY(-1);
	display: flex;
	width: 100vw;
	align-items: center;
	justify-content: center;
	padding: 32rpx 20rpx;
}

.wrapper .status text {
	font-size: 24rpx;
	display: inline-block;
	background: #ccc;
	color: #fff;
	border-radius: 10rpx;
	padding: 6rpx 20rpx;
	line-height: 28rpx;
}
.message-item .message-avatar {
	width: 70rpx;
	height: 70rpx;
	border-radius: 10rpx;
}
.wrapper .message-item {
	transform: scaleY(-1);
	display: flex;
	padding: 10px;
	align-items: flex-start;
}
.wrapper .message-item.me {
	flex-direction: row-reverse;
	display: flex;
}
.chat-record-nickname {
    color: #999999;
    font-size: 24rpx;
    padding: 4rpx 0;
}
.you .chat-record-nickname {
    text-align: left;
}
.me .chat-record-nickname {
    text-align: right;
}
.video-message-box {
	position: relative;
	min-height: 400rpx;
	min-width: 400rpx;
	overflow: hidden;
}
.video-message {
	width: 400rpx;
	height: 400rpx;
}
.video-message-wx {
	width: 400rpx;
	height: 400rpx;
	position: absolute;
}
.video-message-cover {
	height: 400rpx;
	width: 400rpx;
	background: #3F3F3F;
	color: #FFFFFF;
	display: flex;
	align-items: center;
	justify-content: center;
}
.video-message-cover-play {
	text-align: center;
	font-size: 30rpx;
}
.audio-message {
	display: flex;
	align-items: center;
	font-size: 30rpx;
	color: #999999;
}
.audio-message-text {
	padding-left: 20rpx;
}
.message-content-box {
	position: relative;
	max-width: 72%;
	margin: 0 20rpx;
}
.wrapper .message-content {
	padding: 26rpx;
	font-size: 32rpx;
	display: block;
	vertical-align: top;
	border-radius: 10rpx;
	word-wrap: break-word;
	word-break: break-all;
}
.wrapper .message-item.me .message-content {
	color: #FFFFFF;
	background-color: #00b0ff;
}
.wrapper .message-item.you .message-content {
	color: #181818;
	background-color: #fff;
}
.wrapper .message-item .message-content:before {
	position: absolute;
	top: 48rpx;
	display: block;
	width: 16rpx;
	height: 12rpx;
	content: '\00a0';
	-webkit-transform: rotate(29deg) skew(-35deg);
	transform: rotate(29deg) skew(-35deg);
}
.message-content.hide-nickname:before {
	top: 30rpx !important;
}
.wrapper .message-item.you .message-content:before {
	left: -6rpx;
	background-color: #fff;
}

.wrapper .message-item.me .message-content:before {
	right: -6rpx;
	background-color: #00b0ff;
}
.wrapper .message-item.me .file-name {
    color: #FFFFFF;
}
.wrapper .message-item.me .file-size {
    color: #EBEBEB;
}
.im-message-status {
	position: absolute;
	left: -30px;
	bottom: 0;
    font-size: 12px;
    color: #999999;
}
.im-write {
	background-color: #f7f7f7;
	box-shadow: 0 -2rpx 0 #e5e5e5;
	width: 100vw;
	padding: 8rpx 12rpx;
	display: flex;
	align-items: center;
	position: fixed;
	bottom: 0rpx;
	box-sizing: border-box;
}
.write-textarea {
	flex: 8;
	background-color: #FFFFFF;
}
.im-message {
	max-height: 150rpx;
	width: calc(100% - 36rpx);
	outline: none;
	border: none;
	resize: none;
	border-radius: 8rpx;
	padding: 18rpx;
	-webkit-user-select: text !important;
	-moz-user-select: text !important;
	-ms-user-select: text !important;
	user-select: text !important;
	line-height: 40rpx;
	font-size: 32rpx;
	color: #181818;
}
.im-message.disabled {
	background-color: #F8F8F8;
	color: #999999;
	font-size: 28rpx;
	text-align: center;
}
.im-message::-webkit-scrollbar {
	width: 4rpx;
}
.im-message::-webkit-scrollbar-track {
	background-color: #FFFFFF;
	-webkit-border-radius: 2em;
	-moz-border-radius: 2em;
	border-radius: 2em;
}
.im-message::-webkit-scrollbar-thumb {
	background-color: #e6e6e6;
	-webkit-border-radius: 2em;
	-moz-border-radius: 2em;
	border-radius: 2em;
}
.write-right {
	flex: 2;
	display: flex;
	align-items: center;
	justify-content: center;
}
.write-right .toolbar-icon {
	display: inline-block;
	cursor: pointer;
	vertical-align: middle;
	width: 56rpx;
	height: 56rpx;
	content: '';
	margin-left: 16rpx;
}
.send-btn {
	margin-left: 28rpx;
	background: #00b0ff;
	color: #fff;
	border-color: #00b0ff;
	outline: none;
	padding: 10rpx 20rpx;
	font-size: 24rpx;
	line-height: 1.5;
	border-radius: 6rpx;
}
.send-btn::after {
	border: none;
}
.send-btn-hover {
	background-color: #19baff;
}
.footer-tool {
	position: fixed;
	bottom: 0rpx;
	background-color: #fff;
	box-shadow: 0 8rpx 10rpx rgba(0, 0, 0, .1);
	width: 100%;
	animation: show-footer-tool .1s;
	animation-fill-mode: forwards;
	padding: 10rpx;
	box-sizing: border-box;
	height: 170px;
	overflow-y: auto;
	overflow-x: hidden;
}
@keyframes show-footer-tool {
	from {
		height: 0;
	}

	to {
		height: 170px;
	}
}
.footer-tool .emoji-img {
	display: inline-block;
	height: 72rpx;
	width: 72rpx;
	padding: 10rpx;
}
.footer-tool .emoji-img image{
	height: 52rpx;
	width: 52rpx;
}
.footer-tool .emoji-img-hover {
	background: #F2F3F4;
}
.toolbar {
	display: flex;
	flex-wrap: wrap;
	padding: 10rpx;
}
.toolbar-item {
	width: 25%;
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: center;
	padding: 10rpx;
}
.toolbar-item-hover {
	background-color: #F2F3F4;
}
.toolbar-item image {
	width: 50rpx;
	height: 50rpx;
	padding: 10rpx 0;
}
.toolbar-item view {
	display: block;
	width: 100%;
	font-size: 28rpx;
	line-height: 34rpx;
	text-align: center;
}
.fastim-color-blue {
	color: #6388fb;
}
.fastim-color-red {
	color: #f74c31;
}
.session-user-input-status-box {
	position: relative;
}
.session-user-input-status {
	position: absolute;
    color: #6388fb;
    width: 220rpx;
    bottom: 54rpx;
}
.link-popup-box {
	padding: 80rpx 20rpx 20rpx 20rpx;
	text-align: center;
	font-size: 28rpx;
}
.link-popup-title {
	font-weight: bold;
	font-size: 30rpx;
}
.link-popup-url {
	display: block;
	width: 90%;
	margin: 0 auto;
	color: #6388fb;
	padding: 20rpx 0;
	word-break: break-all;
	word-wrap: break-word;
}
.link-popup-tis {
	color: #999999;
}
.link-popup-buttons {
	padding-top: 20rpx;
	display: flex;
	height: 160rpx;
	align-items: center;
	justify-content: center;
}
.link-popup-button-item {
	margin-right: 40rpx;
}
.link-popup-url-button {
	display: inline-block;
	height: 70rpx;
	line-height: 70rpx;
	padding: 0 80rpx;
}
.wrapper .message-item.me .link-message {
	color: #f74c31;
}
.message-longpress-action {
	position: fixed;
	top: 400rpx;
	left: 200rpx;
	background: #262626;
	color: #BDBDBD;
	font-size: 28rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 9999;
	border-radius: 16rpx;
}
.longpress-action-item {
	display: block;
	padding: 20rpx 24rpx;
	border-left: 1px solid #5E5E5E;
}
.longpress-action-item:first-child{
	border: none;
}
.longpress-action-pin {
	position: fixed;
	top: 466rpx;
	left: 219rpx;
}
.mask {
	z-index: 9990;
	position: fixed;
	top: 0;
	width: 100vw;
	height: 100vh;
}
.reply-list {
	padding: 0 20rpx;
}
.reply-item {
	display: block;
	font-size: 28rpx;
	border-bottom: 1px solid #F2F2f2;
	padding: 20rpx 0;
	overflow:hidden;
	text-overflow:ellipsis;
	display:-webkit-box;
	-webkit-box-orient:vertical;
	-webkit-line-clamp:2;
}
.reply-item:last-child {
	border-bottom: none;
}
</style>
