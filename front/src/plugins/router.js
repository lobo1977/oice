import Vue from 'vue'
import Router from 'vue-router'
import Building from '@/components/Building/Index'
import Customer from '@/components/Customer/Index'
import My from '@/components/My/Index'
import Login from '@/components/Login'
import Mobile from '@/components/Mobile'

Vue.use(Router)

export default new Router({
  base: '/app/',
  mode: 'history',
  routes: [
    {
      path: '/',
      name: 'Home',
      redirect: '/building'
    }, {
      path: '/building',
      name: 'Building',
      meta: {
        title: '房源',
        showPlus: true,
        keepAlive: true
      },
      component: Building
    }, {
      path: '/building/view/:id',
      name: 'BuildingView',
      meta: {
        title: '',
        showBack: true,
        showPlus: true,
        hideBar: true
      },
      component: function (resolve) {
        require(['../components/Building/View.vue'], resolve)
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
        require(['../components/Building/Edit.vue'], resolve)
      }
    }, {
      path: '/unit/view/:id',
      name: 'Unit',
      meta: {
        title: '',
        showBack: true,
        hideBar: true
      },
      component: function (resolve) {
        require(['../components/Building/Unit.vue'], resolve)
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
        require(['../components/Building/UnitEdit.vue'], resolve)
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
        hideBar: true
      },
      component: function (resolve) {
        require(['../components/Customer/view.vue'], resolve)
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
        require(['../components/Customer/Edit.vue'], resolve)
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
        require(['../components/Customer/Log.vue'], resolve)
      }
    }, {
      path: '/linkman/view/:id',
      name: 'Linkman',
      meta: {
        title: '',
        showBack: true,
        hideBar: true
      },
      component: function (resolve) {
        require(['../components/Linkman/view.vue'], resolve)
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
        require(['../components/Linkman/Edit.vue'], resolve)
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
        require(['../components/Recommend/Index.vue'], resolve)
      }
    }, {
      path: '/recomend/view/:id',
      name: 'RecommendView',
      meta: {
        title: '项目推荐',
        hideBar: true
      },
      component: function (resolve) {
        require(['../components/Recommend/View.vue'], resolve)
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
        require(['../components/My/Info.vue'], resolve)
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
        require(['../components/My/Mobile.vue'], resolve)
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
        require(['../components/Company/Index.vue'], resolve)
      }
    }, {
      path: '/company/view/:id',
      name: 'CompanyView',
      meta: {
        title: '企业信息',
        showBack: true,
        hideBar: true,
        requiresAuth: true
      },
      component: function (resolve) {
        require(['../components/Company/View.vue'], resolve)
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
        require(['../components/Company/Edit.vue'], resolve)
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
        require(['../components/Company/User.vue'], resolve)
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
        require(['../components/My/favorite.vue'], resolve)
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
        require(['../components/My/Password.vue'], resolve)
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
      hideBar: true,
      component: function (resolve) {
        require(['../components/common/404.vue'], resolve)
      }
    }
  ]
})
