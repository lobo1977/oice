<template>
  <div>
    <flexbox :gutter="0">
        <flexbox-item :span="6">
          <group gutter="0" label-width="7em" label-margin-right="0">
            <cell v-for="(item, index) in questionA" :key="index" :title="item.q + ' = '" :value="item.answer" value-align="left"></cell>
          </group>
        </flexbox-item>
        <flexbox-item :span="6">
          <group gutter="0" label-width="7em" label-margin-right="0">
            <cell v-for="(item, index) in questionB" :key="index" :title="item.q + ' = '" :value="item.answer" value-align="left"></cell>
          </group>
        </flexbox-item>
    </flexbox>

    <flexbox :gutter="0" class="bottom-bar">
      <flexbox-item :span="6">
        <x-button type="primary" @click.native="newQuestions">重新出题</x-button>
      </flexbox-item>
      <flexbox-item :span="6">
        <x-button type="warn" @click.native="math">计算答案</x-button>
      </flexbox-item>
    </flexbox>
  </div>
</template>

<script>
import { Flexbox, FlexboxItem, Group, Cell, XButton } from 'vux'

export default {
  components: {
    Flexbox, FlexboxItem, Group, Cell, XButton
  },
  data () {
    return {
      questionA: [],
      questionB: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.newQuestions()
      if (vm.$isWechat()) {
        let shareLink = window.location.href
        let shareTitle = '逸城小学一年级数学作业'
        vm.$wechatShare(null, shareLink, shareTitle, '', null)
      }
    })
  },
  methods: {
    newQuestions () {
      let vm = this
      vm.questionA = []
      vm.questionB = []
      let num1, num2, num3, symbol1, symbol2, answer
      for (let i = 0; i < 10; i++) {
        symbol1 = vm.randomSymbol()
        symbol2 = vm.randomSymbol()
        num1 = vm.randomNum(18, 99)
        num2 = vm.randomNum(2, 9)
        num3 = vm.randomNum(2, 9)
        if (num1 > 80) {
          symbol1 = '-'
          symbol2 = '-'
        }
        answer = num1 + (symbol1 === '+' ? num2 : num2 * -1) + (symbol2 === '+' ? num3 : num3 * -1)
        vm.questionA.push({
          'q': num1 + ' ' + symbol1 + ' ' + num2 + ' ' + symbol2 + ' ' + num3,
          'a': answer,
          'answer': ''
        })

        num1 = vm.randomNum(18, 99)
        num2 = vm.randomNum(2, 9)
        num3 = vm.randomNum(2, 9)
        if (num1 > 80) {
          symbol1 = '-'
        }
        symbol1 = vm.randomSymbol()
        if (num2 < num3) {
          symbol2 = '+'
        } else {
          symbol2 = vm.randomSymbol()
        }
        let rightAnswer = num2 + (symbol2 === '+' ? num3 : num3 * -1)
        answer = num1 + (symbol1 === '+' ? rightAnswer : rightAnswer * -1)
        vm.questionB.push({
          'q': num1 + ' ' + symbol1 + ' (' + num2 + ' ' + symbol2 + ' ' + num3 + ')',
          'a': answer,
          'answer': ''
        })
      }
    },
    math () {
      this.questionA.forEach(element => {
        element.answer = element.a
      })
      this.questionB.forEach(element => {
        element.answer = element.a
      })
    },
    randomNum (minNum, maxNum) {
      switch (arguments.length) {
        case 1:
          return parseInt(Math.random() * minNum + 1, 10)
        case 2:
          return parseInt(Math.random() * (maxNum - minNum + 1) + minNum, 10)
        default:
          return 0
      }
    },
    randomSymbol () {
      let num = this.randomNum(0, 9)
      if (num % 2 === 0) return '+'
      else return '-'
    }
  }
}
</script>

<style lang="less">
</style>