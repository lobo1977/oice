import Vue from 'vue'
import Router from 'vue-router'
import Math from '../views/Math'

Vue.use(Router)

export default new Router({
  base: '/study/',
  mode: 'history',
  routes: [
    {
      path: '/',
      name: 'Home',
      redirect: '/math'
    },
    {
      path: '/.html',
      redirect: '/math'
    }, {
      path: '/math',
      name: 'Math',
      meta: {
        title: '数学作业',
        hideBar: true,
        showMenu: true,
        showPrint: true
      },
      component: Math
    }, {
      path: '*',
      meta: {
        title: '出错了',
        hideBar: true
      },
      component: function (resolve) {
        require(['@/components/404.vue'], resolve)
      }
    }
  ]
})
