import imConfig from "./config.js"; // 本地配置数据
var ws = {
	that: null,
	pageThat: null,
	socketTask: null,
	socketOpen: false,
	needReconnect: true,
	reconnecting: false,
	sessionId: 0,
	timer: null, // 全局计时器
	errorMsg: [], // 发送失败的消息
	maxReconnectCount: 5, // 最大重连次数
	currentReconnectCount: 0,
	initializeData: false, // 初始化请求来的基础数据
	connectSuccess: null,
	showMsgCallback: null,
	sessionShow: [],
	pageRefresh: {
		session: false,
		message: false,
		addressList: false
	},
	userPlatform: null,
	innerAudioContext: null,
	onMessageCallBack: new Map(),
	init: function (token = '') {
		var that = this
		if (this.socketTask && this.socketOpen) {
			// console.log('无需链接 ws')
			return false;
		}
		
		if (!this.initializeData) {
			// 发送初始化请求
			this.that.$u.get(this.buildUrl('initialize', token), {}).then(res => {
				
				if (res.code == 402) {
					uni.removeStorageSync('userinfo');
					that.pageRefresh.session = true
					uni.reLaunch({
						url: '/pages/user/login'
					})
					return false;
				} else if (res.code == 401) {
					uni.showModal({
						title: '温馨提示',
						content: '登录状态已丢失，您可能在其他端重新登录过或注销了登录~',
						showCancel: false,
						success() {
							that.logout()
						}
					})
					return false;
				} else if (res.code != 1) {
					uni.showModal({
						title: '温馨提示',
						content: '初始化失败,请重试！',
						showCancel: false
					})
					return false;
				}
		
				that.initializeData = {
					config: res.data.config,
					fastReply: res.data.fast_reply,
					tokens: res.data.token_list,
					userinfo: res.data.user_info
				}
				
				// 来信提示音初始化
				that.innerAudioContext = uni.createInnerAudioContext();
				that.innerAudioContext.src = that.buildUrl('message_prompt', that.initializeData.tokens.kefu_token);

				that.connect()
			})
			this.userPlatform = uni.getSystemInfoSync().platform
		} else {
			that.connect()
		}
	},
	connect: function () {
		var that = this
		if (imConfig.httpsSwitch && parseInt(that.initializeData.config.wss_switch) != 1) {
			uni.showModal({
				title: '温馨提示',
				content: 'https下须创建wss服务才能连接网络，请参考文档!',
				showCancel: false
			})
			return false;
		}
		
		// 开始链接 ws
		let ws = uni.connectSocket({
			url: that.buildUrl('ws', that.initializeData.tokens.kefu_token),
			header: {
				'content-type': 'application/json'
			},
			complete: res => {}
		});
		
		uni.onSocketOpen(function(res) {
			console.log('链接已打开')
			that.socketOpen = true
			that.socketTask = ws
			that.currentReconnectCount = 0;
			that.needReconnect = true;
			that.pageThat.commonTips = ''
		
			that.clearTimer()
			
			// 重新发送所有出错的消息
			for (let i in that.errorMsg) {
				that.send(that.errorMsg[i]);
			}
			that.errorMsg = [];
		
			that.timer = setInterval(function() {
				that.send({c: 'Message', a: 'ping'})
			}, 28000); //定时发送心跳
		});
		
		uni.onSocketMessage(function(res) {
			let msg = JSON.parse(res.data)
			that.onMessage(msg);
		});
		
		uni.onSocketError(function(res) {
			that.socketOpen = false;
			that.socketTask = null;
			that.reconnecting = false;
			that.pageThat.commonTips = '网络不给力，正在自动重连~'
			that.reconnect(); // 重连
			console.log('链接出错', res)
		});
		
		// 链接关闭
		uni.onSocketClose(function(res) {
			console.log('链接已关闭', res)
			that.socketOpen = false;
			that.socketTask = null;
			that.reconnecting = false;
			that.clearTimer()
		
			that.pageThat.commonTips = '网络不给力，正在自动重连~'
			
			if (typeof that.closeCallback == 'function') {
				that.closeCallback()
				that.closeCallback = null
			}
			that.reconnect(); // 重连
		});
	},
	send: function (message) {
		var that = this
		if (!message) {
			return;
		}
		
		if (that.socketTask && that.socketOpen) {
			uni.sendSocketMessage({
				data: JSON.stringify(message),
				fail: res => {
					console.log('消息发送出错', message, res)
					that.errorMsg.push(message);
				}
			});
		} else {
			console.log('消息发送出错-ws链接异常', message, that.socketTask, that.socketOpen)
			that.errorMsg.push(message);
		}
	},
	clearTimer: function () {
		if (this.timer != null) {
			clearInterval(this.timer);
			clearTimeout(this.timer);
		}
	},
	pushCid: function(type = 'save'){
		if (parseInt(this.initializeData.config.uni_push_switch) == 0) {
			return false;
		}
		// #ifdef APP-PLUS
		var callBack = (info) => {
			this.send({
				c: 'Message',
				a: 'pushCid',
				data: {
					clientid: info.clientid,
					platform: this.userPlatform,
					type: type
				}
			});
		}
		
		let info = plus.push.getClientInfo();
		if (info && info.clientid) {
			callBack(info)
		} else {
			var obtainingCIDTimer = setInterval(() => {
				info = plus.push.getClientInfo();
				if (info && info.clientid) {
					callBack(info)
					clearInterval(obtainingCIDTimer);
				}
			}, 50)
		}
		// #endif
	},
	reconnect: function () {
		if (!this.needReconnect || this.reconnecting) {
			return false;
		}
		
		this.reconnecting = true
		
		if (this.currentReconnectCount < this.maxReconnectCount) {
			this.currentReconnectCount++;
			if (this.currentReconnectCount == 1) {
				this.init();
				console.log('正在重连 WebSocket 第' + this.currentReconnectCount + '次');
			} else {
				console.log('6秒后重连 WebSocket 第' + this.currentReconnectCount + '次');
				var that = this
				this.timer = setTimeout(function() {
					that.init();
					console.log('正在重连 WebSocket 第' + that.currentReconnectCount + '次');
				}, 6000)
			}
		} else {
			this.clearTimer()
			console.log('18秒后将再次尝试重连 WebSocket')
			this.timer = setTimeout(() => {
				console.log('正在重连...')
				this.init()
			}, 18000); //每18秒重新连接一次
		}
	},
	onMessage: function (msg) {
		var that = this
		var msgFun = new Map([
			['default', () => {
				console.log('默认处理')
			}],
			['clear', () => {
				// ws 链接被服务端清退
				that.needReconnect = false
				if (msg.msg) {
					uni.showModal({
						title: '温馨提示',
						content: msg.msg,
						showCancel: false,
						success: () => {
							that.logout()
						}
					})
				}
				that.send({
					c: 'Message',
					a: 'clear',
				});
			}],
			['show_msg', () => {
				uni.showToast({
					title: msg.msg,
					icon: 'none'
				})
				typeof that.showMsgCallback == 'function' && that.showMsgCallback()
				that.showMsgCallback = null
			}],
			['initialize', () => {
				msg.data.user_info.avatar = that.imgUrl(msg.data.user_info.avatar)
				that.initializeData.userinfo = msg.data.user_info
				
				typeof that.connectSuccess == 'function' && that.connectSuccess()
				that.connectSuccess = null
				
				// 单设备重复在线检测(微信小程序重连后会有单实例多个连接的情况,且在同一句柄上无法关闭)
				// #ifdef MP-WEIXIN
				that.send({
					c: 'Message',
					a: 'equipmentInspection',
					data: {
						type: 'check',
						time: Date.now()
					}
				});
				// #endif
				
				that.pushCid()
			}],
			['equipment-inspection', () => {
				let time = msg.data.time.toString()
				if (that.equipmentLastInspectionTime == time) {
					that.send({
						c: 'Message',
						a: 'equipmentInspection',
						data: {
							type: 'close',
							clientid: msg.data.clientid
						}
					});
				}
				that.equipmentLastInspectionTime = time
			}],
			['new_message', () => {
				let pages = getCurrentPages(), page = pages[pages.length - 1]
				msg.data.unreadMessagesNumber = msg.data.unread_msg_count
				if (page.route == 'pages/session/session') {
					that.imSession(msg.data, that.pageThat);
				} else {
					that.sessionShow.push((pageThat = that.pageThat) => {
						that.imSession(msg.data, pageThat);
					})
				}
				
				var readMessage = () => {
					that.send({
						c: 'Message',
						a: 'readMessage',
						data: {
							record_id: msg.data.record_id,
							session_id: msg.data.id
						}
					});
					
					// 清理未读消息
					that.sessionShow.push((pageThat = that.pageThat) => {
						that.imSession({
							id: msg.data.id,
							unreadMessagesNumber: 0
						}, pageThat, false)
					})
					
					that.newMessageNotice();
				}
				
				if ((page.route == 'pages/message/message') && (parseInt(that.pageThat.id) == msg.data.id)) {
					readMessage()
					
					// 消息追加
					if (that.pageThat.messageList[0]) {
						that.pageThat.messageList[0].data.unshift(that.formatMessage({
							...msg.data,
							id: new Date().getTime() + msg.data.id + Math.floor(Math.random() * 10000),
						}));
					} else {
						that.pageThat.messageList.unshift({
							datetime: '刚刚',
							data: [that.formatMessage({
								...msg.data,
								id: new Date().getTime() + msg.data.id + Math.floor(Math.random() * 10000),
							})]
						});
					}
					
					that.pageThat.scrollIntoFooter()
					that.pageThat.unexpectedRecords++
				} else {
					that.newMessageNotice();
				}
			}],
			['session-list', () => {
				var session = msg.data
				
				if (session.length) {
					for (var i = 0; i < session.length; i++) {
						if (parseInt(session[i].online) == 0) {
							session[i].avatar_gray = 'im-img-gray'
							session[i].user_status = '[离线]'
						} else {
							session[i].avatar_gray = ''
							session[i].user_status = ''
						}
						session[i].avatar = that.imgUrl(session[i].avatar)
						session[i].unreadMessagesNumber = session[i].unread_msg_count
					}
					that.pageThat.sessionList = session
					that.pageThat.loadStatus = false;
				} else {
					that.pageThat.sessionList = []
					that.pageThat.loadStatus = '没有更多了...'
				}
				
				if (typeof that.loadSessionReady == 'function') {
					setTimeout(() => {
						that.loadSessionReady()
						that.loadSessionReady = null
					}, 200)
				}
			}],
			['logout', () => {
				that.logout()
			}],
			['reload-session-list', () => {
				let pages = getCurrentPages(), page = pages[pages.length - 1]
				var sessionShow = function (pageThat = that.pageThat) {
					that.pageFun(pageThat.pageDataLoad, pageThat);
				}
				if (page.route == 'pages/session/session') {
					sessionShow(that.pageThat)
					sessionShow = null
				} else {
					that.sessionShow.push(sessionShow)
				}
			}],
			['session-setting', () => {
				msg.data.session_user_info.avatar = that.imgUrl(msg.data.session_user_info.avatar)
				that.pageThat.info = msg.data.session_user_info
			}],
			['blacklist', () => {
				let pages = getCurrentPages(), page = pages[pages.length - 1]
				if (page.route == 'pages/session/info' && that.pageThat.info.session_user == msg.data.session_user) {
					if (msg.data.action == 'del') {
						that.pageThat.info.blacklist = false
					} else if (msg.data.action == 'add') {
						that.pageThat.info.blacklist = true
					}
				}
			}],
			['action_session', () => {
				if (msg.data.action == 'transfer') {
					let csrs = []
					for (let c in msg.data.csr_list) {
						csrs.push({
							value: msg.data.csr_list[c].admin_id,
							label: msg.data.csr_list[c].nickname
						})
					}
					if (csrs.length) {
						that.pageThat.transferSelectList = csrs
						that.pageThat.transferShow = true
					} else {
						uni.showModal({
							title: '温馨提示',
							content: '没有其他的客服代表在线~',
							showCancel: false
						})
						that.pageThat.transferUser = ''
						that.pageThat.transferShow = false
					}
				} else if (msg.data.action == 'transfer_done') {
					uni.showToast({
						title: '会话已转接~',
						icon: 'none'
					})
					that.onMessage({
						msgtype: 'reload-session-list'
					})
				}
			}],
			['search_user', () => {
				that.onMessage({
					msgtype: 'session-list',
					data: msg.data
				})
			}],
			['message_input', () => {
				let pages = getCurrentPages(), page = pages[pages.length - 1]
				if (page.route == 'pages/message/message' && that.pageThat.id == msg.data.session_id) {
					if (msg.data.type == 'input') {
						that.pageThat.sessionUserInputStatus = true
					} else {
						that.pageThat.sessionUserInputStatus = false
					}
				}
			}],
			['send_message', () => {
				if (!msg.data.message_id) {
					return;
				}
				
				// 倒序循环-最快速度找到刚发送的消息
				if (that.pageThat && that.pageThat.messageList) {
					for (var i = (that.pageThat.messageList.length - 1); i >= 0; i--) {
						for (var y = (that.pageThat.messageList[i].data.length - 1); y >= 0; y--) {
							if (that.pageThat.messageList[i].data[y].id == msg.data.message_id) {
								that.pageThat.messageList[i].data[y].id = msg.data.id;
								that.pageThat.messageList[i].data[y].status = (msg.code == 1) ? 0 : 3;
							}
						}
					}
				}
				
				if (msg.code == 0) {
					uni.showToast({
						title: msg.data.msg,
						icon: 'none'
					})
				}
			}],
			['read_message_done', () => {
				let pages = getCurrentPages(), page = pages[pages.length - 1]
				if (page.route == 'pages/message/message' && parseInt(msg.data.session_id) == parseInt(that.pageThat.id)) {
					for (let m in that.pageThat.messageList) {
						for (let y in that.pageThat.messageList[m].data) {
							if (that.pageThat.messageList[m].data[y].sender == 'me' && that.pageThat.messageList[m].data[y].status != 1) {
								that.pageThat.messageList[m].data[y].status = 1
								break;
							}
						}
					}
				}
			}],
			['address-list', () => {
				that.pageThat.indexList = msg.data.initialPinyinIndex
				that.pageThat.users = msg.data.initialPinyinArr
			}],
			['upload_multipart', () => {
				if (msg.data.upload_multipart) {
					that.initializeData.config.upload.multipart = msg.data.upload_multipart
					let mu = new Object();
					for (let i in msg.data.upload_multipart) {
						mu[i] = msg.data.upload_multipart[i]
					}
					that.pageThat.uploadFormData = mu
				}
			}],
			['user-all-fast-reply', () => {
				that.initializeData.fastReply = msg.data
			}],
			['chat_record', () => {
				// 会话保障
				let status = ['离线', '繁忙', '离开', '在线'];
				var info = msg.data.session_info
				info.avatar = that.imgUrl(info.avatar)
				info.status = {
					chinese: status[info.status],
					value: info.status
				}
				that.pageThat.info = info
				that.pageThat.loadRecordsData = msg.data.next_page;
				
				// 清理未读消息
				that.sessionShow.push((pageThat = null, messageThat = that.pageThat) => {
					that.imSession({
						id: messageThat.id,
						unreadMessagesNumber: 0
					}, pageThat, false)
				})
				
				for (let i in msg.data.chat_record) {
					for (let m in msg.data.chat_record[i].data) {
						msg.data.chat_record[i].data[m] = that.formatMessage(msg.data.chat_record[i].data[m])
					}
				}
				
				if (msg.data.page == 1) {
					that.pageThat.messageList = msg.data.chat_record;
					// 滑动到最后一条消息
					that.pageThat.scrollIntoFooter();
				} else {
					for (let i = msg.data.chat_record.length - 1; i >= 0; i--) {
						that.pageThat.messageList.push(msg.data.chat_record[i]);
					}
				}
			}],
			['user-info', () => {
				var pageTitle = '查看资料';
				let pages = getCurrentPages(), page = pages[pages.length - 1], info = msg.data.info
				if (msg.data.data.method == 'edit') {
					pageTitle = '编辑资料';
					that.pageThat.showAvatarUpload = true;
					that.pageThat.avatarFileList = [{
						url: info.avatar
					}]
					if (that.pageThat.id == 0) {
						that.pageThat.detail = [
							{
								title: '昵　　称',
								placeholderTitle: '昵称',
								type: 'input',
								name: 'nickname',
								value: info.nickname
							}
						]
					} else {
						that.pageThat.detail = [
							{
								title: '昵　　称',
								placeholderTitle: '昵称',
								type: 'input',
								name: 'nickname',
								value: info.nickname_origin
							},
							{
								title: '联系方式',
								placeholderTitle: '联系方式',
								type: 'input',
								name: 'contact',
								value: info.contact
							},
							{
								title: '用户来路',
								placeholderTitle: '用户来路',
								type: 'textarea',
								name: 'referrer',
								value: info.referrer
							},
							{
								title: '备注',
								placeholderTitle: '备注，仅客服代表可见',
								type: 'textarea',
								name: 'note',
								value: info.note
							}
						]
					}
					
					uni.setNavigationBarTitle({
						title: pageTitle
					});
					return false;
				}
				
				let status = ['离线', '繁忙', '离开', '在线'];
				info.status = {
					chinese: status[info.status],
					value: info.status
				}
				that.pageThat.info = info
				
				if (page.route == 'pages/user/info') {
					pageTitle = '查看资料';
					
					that.pageThat.detail = [
						{
							title: '账号',
							value: info.id
						},
						{
							title: '身份',
							value: info.source == 'csr' ? '客服代表' : '用户'
						}
					];
					
					if (that.pageThat.id == 0) {
						pageTitle = '我的资料';
						
						that.pageThat.buttons = [{
							action: 'userinfo-opt',
							btype: 'default',
							opt: 'edit',
							name: '编辑资料'
						}];
					} else {
						that.pageThat.detail.push(
						{
							title: '联系方式',
							value: info.contact
						},
						{
							title: '管理备注',
							value: info.note
						},
						{
							title: '用户来源',
							value: info.referrer
						});
						that.pageThat.buttons = [{
								action: 'open-session',
								btype: 'success',
								name: '发送消息'
							},
							{
								action: 'userinfo-opt',
								btype: 'default',
								opt: 'edit',
								name: '编辑资料'
							}
						];
					}
					
					for (let d in that.pageThat.detail) {
						that.pageThat.detail[d].value = that.pageThat.detail[d].value ? that
							.pageThat.detail[d].value : '-'
					}
					
					uni.setNavigationBarTitle({
						title: pageTitle
					});
				}
			}],
			['fast-reply', () => {
				if (msg.data.data.method == 'edit') {
					msg.data.info.status = parseInt(msg.data.info.status) == 0 ? false : true
					that.pageThat.form = msg.data.info
					return;
				}
				
				if (msg.data.data.nextpage) {
					that.pageThat.loadFastReply = msg.data.data;
					that.pageThat.loadFastReply.method = 'get'
				} else {
					that.pageThat.loadFastReply = false
				}
				
				for (let f in msg.data.fastreply) {
					msg.data.fastreply[f].status = parseInt(msg.data.fastreply[f].status)
				}
				
				if (parseInt(msg.data.data.page) == 1) {
					that.pageThat.quickReply = msg.data.fastreply
				} else {
					that.pageThat.quickReply = that.pageThat.quickReply.concat(msg.data
						.fastreply)
				}
			}],
			['blacklists', () => {
				if (msg.data.data.nextpage) {
					that.pageThat.loadData = msg.data.data;
					that.pageThat.loadData.method = 'get'
				} else {
					that.pageThat.loadData = false
				}
				
				if (parseInt(msg.data.data.page) == 1) {
					that.pageThat.users = msg.data.users
				} else {
					that.pageThat.users = that.pageThat.users.concat(msg.data
						.users)
				}
			}],
			['trajectory', () => {
				that.pageThat.chat_record_page = msg.data.next_page;
				for (let t in msg.data.trajectory) {
					for (let tt in msg.data.trajectory[t]) {
						msg.data.trajectory[t][tt].note = that.buildTrajectory(msg.data.trajectory[t][tt])
					}
				}
				
				if (parseInt(msg.data.page) == 1) {
					that.pageThat.trajectory = msg.data.trajectory
				} else {
					var concats = []
					for (let t in that.pageThat.trajectory) {
						for (let t2 in msg.data.trajectory) {
							if (t == t2) {
								that.pageThat.trajectory[t] = that.pageThat.trajectory[t].concat(msg.data.trajectory[t2])
								concats.push(t2)
							}
						}
					}
					
					for (let t2 in msg.data.trajectory) {
						if (concats.includes(t2) === false) {
							that.pageThat.trajectory[t2] = msg.data.trajectory[t2]
						}
					}
				}
			}],
			['online', () => {
				if (msg.modulename == 'index') {
					let pages = getCurrentPages(), page = pages[pages.length - 1]
					if (page.route == 'pages/session/session') {
						for (let m in that.pageThat.sessionList) {
							if (that.pageThat.sessionList[m].session_user == msg.user_id) {
								that.pageThat.sessionList[m].user_status = ''
								that.pageThat.sessionList[m].avatar_gray = ''
							}
						}
					} else if (page.route == 'pages/message/message' && that.pageThat.info && that.pageThat.info.session_user == msg.user_id) {
						that.pageThat.info.status = {
							chinese: '在线',
							value: 3
						}
					}
				}
			}],
			['offline', () => {
				let pages = getCurrentPages(), page = pages[pages.length - 1]
				if (page.route == 'pages/session/session') {
					for (let m in that.pageThat.sessionList) {
						if (that.pageThat.sessionList[m].session_user == msg.user_id) {
							that.pageThat.sessionList[m].user_status = '[离线]'
							that.pageThat.sessionList[m].avatar_gray = 'im-img-gray'
						}
					}
				} else if (page.route == 'pages/message/message' && that.pageThat.info && that.pageThat.info.session_user == msg.user_id) {
					that.pageThat.info.status = {
						chinese: '离线',
						value: 0
					}
				}
			}]
		]);
		let onMessageCallBackAction = this.onMessageCallBack.get(msg.msgtype)
		if (onMessageCallBackAction) {
			onMessageCallBackAction.call(that);
			this.onMessageCallBack.delete(msg.msgtype)
		}
		let action = msgFun.get(msg.msgtype) || msgFun.get('default')
		return action.call(that);
	},
	newMessageNotice: function () {
		// 新消息通知
		var that = this
		
		// 震动
		let new_message_shake = parseInt(that.initializeData.config.new_message_shake)
		if (new_message_shake == 3 || new_message_shake == 2) {
			uni.vibrateLong({
			    success: function () {}
			});
		}
		
		// 铃声
		that.newMessageRinging()
	},
	newMessageRinging: function () {
		if (this.innerAudioContext) {
			this.innerAudioContext.play();
			setTimeout(() => {
				this.innerAudioContext.stop();
			}, 1500)
		} else {
			console.error('来信提示音播放失败！');
		}
	},
	imSession: function (data, pageThat, moveTop = true) {
		// session页数据保障
		var currentSessionIndex = -1;
		for (let m in pageThat.sessionList) {
			if (pageThat.sessionList[m].id == data.id) {
				currentSessionIndex = m;
				pageThat.sessionList[m].unreadMessagesNumber = (data.unreadMessagesNumber !== false) ? data.unreadMessagesNumber:pageThat.sessionList[m].unreadMessagesNumber
				if (data.last_time) {
					pageThat.sessionList[m].last_time = data.last_time
					pageThat.sessionList[m].last_message = data.last_message
				}
				break;
			}
		}
		
		if (currentSessionIndex !== -1) {
			
			if (moveTop) {
				let currentSessionTemp = pageThat.sessionList[currentSessionIndex]
				pageThat.sessionList = pageThat.sessionList.filter(item => {
					return item.id != data.id;
				})
				pageThat.sessionList.unshift(currentSessionTemp);
			}
		} else {
			// 添加会话
			if (data.online && parseInt(data.online) == 0) {
				data.avatar_gray = 'im-img-gray'
				data.user_status = '[离线]'
			} else {
				data.avatar_gray = ''
				data.user_status = ''
			}
			
			data.avatar = this.imgUrl(data.avatar)
			pageThat.sessionList.unshift(data);
			this.pageRefresh.addressList = true
		}
	},
	buildTrajectory: function (data) {
		const log_data = new Map([
			[0, ['访问', data.note + '访问页面：' + data.url]],
			[1, ['被邀请', '']],
			[2, ['开始对话', '']],
			[3, ['拒绝对话', '']],
			[4, ['客服分配', '']],
			[5, ['用户离开', '用户的workerman链接已断开']],
			[6, ['留言', '用户留言了']],
			[7, ['其他', '']],
			[8, ['转移会话', '']],
			['default', ['未知', '']],
		]);
		let log = log_data.get(parseInt(data.log_type)) || log_data.get('default');
		return log[1] ? log[1] : data.note;
	},
	formatMessage: function(message) {
		if (message.message_type == 4 || message.message_type == 5) {
			var message_arr = message.message.split('#');
			var message_obj = {};
			
			for (let y in message_arr) {
				let message_temp = message_arr[y].split('=');
				if (typeof message_temp[1] != 'undefined') {
					message_obj[message_temp[0]] = message_temp[1];
				}
			}
			message.message = message_obj;
		} else if (message.message_type == 2) {
			var file_name = message.message.split('.');
			message.suffix = file_name[file_name.length - 1];
		}
		return message;
	},
	clearPageRefresh: function() {
		for (let p in this.pageRefresh) {
			this.pageRefresh[p] = false
		}
	},
	imgUrl: function(url) {
		var ret = /^http(s)?:\/\//;
		var retDataImage = /^data:image/;
		if (ret.test(url) || retDataImage.test(url)) {
			return url;
		} else {
			if (this.initializeData.config && this.initializeData.config.__CDN__) {
				return this.initializeData.config.__CDN__ + url;
			} else {
				return this.buildUrl('default') + url;
			}
		}
	},
	buildUrl: function(type, token = false, data = null) {
		var that = this
		var protocol = imConfig.httpsSwitch ? 'https://' : 'http://';
		var port = imConfig.httPort ? ':' + imConfig.httPort : '';
	
		var buildFun = new Map([
			['initialize', () => {
				return protocol + imConfig.baseUrl + port +
					'/addons/kefu/index/initialize?modulename=admin&token=' + token;
			}],
			['message_prompt', () => {
				if (that.initializeData.config.upload.cdnurl) {
					return that.initializeData.config.upload.cdnurl + that.initializeData.config.ringing;
				}
				return protocol + imConfig.baseUrl + port + that.initializeData.config.ringing;
			}],
			['ws', () => {
				protocol = parseInt(that.initializeData.config.wss_switch) == 1 ? 'wss://' :
					'ws://';
				return (protocol + imConfig.baseUrl + ':' + that.initializeData.config
				.websocket_port + '/?modulename=admin&platform=uni&token=' + token).replace(
					/\|/g, '~');
			}],
			['upload', () => {
				return that.initializeData.config.upload.uploadurl + '?modulename=admin&token=' + token;
			}],
			['emoji', () => {
				return protocol + imConfig.baseUrl + '/assets/addons/kefu/img' + data;
			}],
			['default', () => {
				return protocol + imConfig.baseUrl + port
			}]
		]);
	
		let action = buildFun.get(type) || buildFun.get('default')
		return action.call(this);
	},
	getEmoji: function () {
		if (!imConfig.expressionUrlBuild) {
			for (let e in imConfig.expression) {
				imConfig.expression[e].src = this.buildUrl('emoji', false, imConfig.expression[e].src)
			}
		}
		this.pageThat.emoji = imConfig.expression
		imConfig.expressionUrlBuild = true
	},
	logout: function () {
		var that = this
		try {
			that.closeCallback = res => {
				uni.hideToast()
				that.initializeData = false
				uni.reLaunch({
					url: '/pages/user/login'
				})
			}
			that.needReconnect = false
			if (that.socketTask && that.socketOpen) {
				that.socketTask.close()
			} else {
				that.closeCallback()
				that.closeCallback = null
			}
			uni.removeStorageSync('userinfo');
			that.pageRefresh.session = true
		} catch (e) {
			console.log(e)
			uni.showToast({
				title: '注销失败,请重试!',
				icon: 'none'
			})
		}
	},
	pageFun: function(fun, pageThat = null) {
		this.pageThat = pageThat ? pageThat : this.pageThat
		if (this.socketOpen) {
			typeof fun == 'function' && fun()
		} else {
			this.connectSuccess = fun
		}
	},
	checkNetwork: function (pageThat = null) {
		this.pageThat = pageThat ? pageThat:this.pageThat
		if (!this.socketTask || !this.socketOpen) {
			this.pageThat.commonTips = '网络不给力，正在自动重连~'
		} else {
			this.pageThat.commonTips = ''
		}
	}
}

export default {
	ws
}