import Vue from 'vue'
import Router from 'vue-router'
import Building from '../views/Building/Index'
import Customer from '../views/Customer/Index'
import My from '../views/My/Index'
import Login from '../views/Login'
import Mobile from '../views/Mobile'

Vue.use(Router)

export default new Router({
  base: '/app/',
  mode: 'history',
  routes: [
    {
      path: '/',
      name: 'Home',
      redirect: '/building'
    },
    {
      path: '/index.html',
      redirect: '/building'
    }, {
      path: '/building',
      name: 'Building',
      meta: {
        title: '房源',
        showPlus: true,
        keepAlive: true,
        canShare: true
      },
      component: Building
    }, {
      path: '/building/view/:id',
      name: 'BuildingView',
      meta: {
        title: '',
        showBack: true,
        showPlus: true,
        hideBar: true,
        canShare: true
      },
      component: function (resolve) {
        require(['../views/Building/View.vue'], resolve)
      }
    }, {
      path: '/building/edit/:id',
      name: 'BuildingEdit',
      meta: {
        title: '',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/Building/Edit.vue'], resolve)
      }
    }, {
      path: '/unit/view/:id',
      name: 'Unit',
      meta: {
        title: '',
        showBack: true,
        hideBar: true,
        canShare: true
      },
      component: function (resolve) {
        require(['../views/Building/Unit.vue'], resolve)
      }
    }, {
      path: '/unit/edit/:id/:bid',
      name: 'UnitEdit',
      meta: {
        title: '',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/Building/UnitEdit.vue'], resolve)
      }
    }, {
      path: '/customer',
      name: 'Customer',
      meta: {
        title: '客户',
        showPlus: true,
        keepAlive: true
      },
      component: Customer
    }, {
      path: '/customer/view/:id',
      name: 'CustomerView',
      meta: {
        title: '',
        showBack: true,
        showPlus: true,
        hideBar: true,
        canShare: true
      },
      component: function (resolve) {
        require(['../views/Customer/view.vue'], resolve)
      }
    }, {
      path: '/customer/edit/:id',
      name: 'CustomerEdit',
      meta: {
        title: '',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/Customer/Edit.vue'], resolve)
      }
    }, {
      path: '/customer/log/:id/:cid',
      name: 'CustomerLog',
      meta: {
        title: '',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/Customer/Log.vue'], resolve)
      }
    }, {
      path: '/linkman/view/:id',
      name: 'Linkman',
      meta: {
        title: '',
        showBack: true,
        hideBar: true,
        canShare: true
      },
      component: function (resolve) {
        require(['../views/Linkman/view.vue'], resolve)
      }
    }, {
      path: '/linkman/edit/:id/:type/:oid',
      name: 'LinkmanEdit',
      meta: {
        title: '',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/Linkman/Edit.vue'], resolve)
      }
    }, {
      path: '/recomend/index/:id',
      name: 'Recommend',
      meta: {
        title: '项目推荐',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/Recommend/Index.vue'], resolve)
      }
    }, {
      path: '/recomend/view/:id',
      name: 'RecommendView',
      meta: {
        title: '项目推荐',
        hideBar: true,
        canShare: true
      },
      component: function (resolve) {
        require(['../views/Recommend/View.vue'], resolve)
      }
    }, {
      path: '/confirm/view/:id',
      name: 'ConfirmView',
      meta: {
        title: '客户确认书',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/Confirm/View.vue'], resolve)
      }
    }, {
      path: '/confirm/edit/:id/:bid/:cid',
      name: 'ConfirmEdit',
      meta: {
        title: '',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/Confirm/Edit.vue'], resolve)
      }
    }, {
      path: '/my',
      name: 'My',
      meta: {
        title: '我的',
        requiresAuth: true,
        keepAlive: true
      },
      component: My
    }, {
      path: '/my/info',
      name: 'Info',
      meta: {
        title: '个人信息',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/My/Info.vue'], resolve)
      }
    }, {
      path: '/my/mobile',
      name: 'ChangeMobile',
      meta: {
        title: '更换手机号码',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/My/Mobile.vue'], resolve)
      }
    }, {
      path: '/my/company',
      name: 'Company',
      meta: {
        title: '我的企业',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/Company/Index.vue'], resolve)
      }
    }, {
      path: '/company/view/:id',
      name: 'CompanyView',
      meta: {
        title: '企业信息',
        showBack: true,
        hideBar: true,
        requiresAuth: true,
        canShare: true
      },
      component: function (resolve) {
        require(['../views/Company/View.vue'], resolve)
      }
    }, {
      path: '/company/edit/:id',
      name: 'CompanyEdit',
      meta: {
        title: '创建企业',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/Company/Edit.vue'], resolve)
      }
    }, {
      path: '/company/user/:id',
      name: 'CompanyUser',
      meta: {
        title: '企业成员',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/Company/User.vue'], resolve)
      }
    }, {
      path: '/my/favorite',
      name: 'Favorite',
      meta: {
        title: '收藏夹',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/My/favorite.vue'], resolve)
      }
    }, {
      path: '/my/password',
      name: 'Password',
      meta: {
        title: '修改密码',
        showBack: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../views/My/Password.vue'], resolve)
      }
    }, {
      path: '/login',
      name: 'Login',
      meta: {
        title: '登录',
        showBack: true,
        hideBar: true
      },
      component: Login
    }, {
      path: '/Mobile',
      name: 'BindMobile',
      meta: {
        title: '绑定手机',
        hideBar: true
      },
      component: Mobile
    }, {
      path: '/wechat/login',
      name: 'WechatLogin'
    }, {
      path: '/logout',
      name: 'Logout'
    }, {
      path: '*',
      meta: {
        hideBar: true
      },
      component: function (resolve) {
        require(['@/components/404.vue'], resolve)
      }
    }
  ]
})
