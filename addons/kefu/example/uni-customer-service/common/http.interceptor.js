import imConfig from "./config.js"; // 本地配置数据
var config = {
	baseUrl: (imConfig.httpsSwitch ? 'https://':'http://') + imConfig.baseUrl, // 请求的域名
	method: 'POST',
	// 设置为json，返回后会对数据进行一次JSON.parse()
	dataType: 'json',
	showLoading: true, // 是否显示请求中的 loading
	loadingText: '稍等片刻...', // 请求loading中的文字提示
	loadingTime: 800, // 在此时间内，请求还没回来的话，就显示加载中动画，单位ms
	originalData: false, // 是否在拦截器中返回服务端的原始数据
	loadingMask: true, // 展示loading的时候，是否给一个透明的蒙层，防止触摸穿透
	// 配置请求头信息
	header: {
		'content-type': 'application/json;charset=UTF-8'
	},
}

const install = (Vue, vm) => {
	Vue.prototype.$u.http.setConfig(config);
	
	Vue.prototype.$u.http.interceptor.request = (config) => {
		const userinfo = uni.getStorageSync('userinfo');
		if (!imConfig.noNeedLogin.includes(config.url)) {
			if (userinfo) {
				config.header.token = userinfo.token;
			} else {
				return false;// 不再请求，app.vue内有延时的登录跳转
			}
		}
		return config;
	}
}

export default {
	install
}