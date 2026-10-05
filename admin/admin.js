(function () {
  'use strict';

  function reindex(list) {
    // после добавления/удаления/перестановки обновляем индексы в именах полей
    var items = list.querySelectorAll(':scope > [data-items] > [data-item]');
    items.forEach(function (item, i) {
      item.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(/\]\[\d+\]\[/, '][' + i + '][');
      });
    });
  }

  document.addEventListener('click', function (e) {
    var t = e.target;
    if (!(t instanceof HTMLElement)) return;
    var list = t.closest('[data-list]');
    if (!list) return;
    var box = list.querySelector(':scope > [data-items]');

    if (t.hasAttribute('data-add')) {
      var max = parseInt(t.getAttribute('data-max'), 10) || 10;
      var count = box.children.length;
      if (count >= max) { alert('Больше ' + max + ' элементов добавить нельзя.'); return; }
      var tpl = list.querySelector(':scope > template[data-tpl]');
      var html = tpl.innerHTML.replace(/__I__/g, String(count));
      var holder = document.createElement('div');
      holder.innerHTML = html;
      var node = holder.firstElementChild;
      box.appendChild(node);
      var first = node.querySelector('input, textarea');
      if (first) first.focus();
    }
    if (t.hasAttribute('data-del')) {
      if (confirm('Удалить этот блок? Он пропадёт с сайта после сохранения.')) {
        t.closest('[data-item]').remove();
        reindex(list);
      }
    }
    if (t.hasAttribute('data-up') || t.hasAttribute('data-down')) {
      var item = t.closest('[data-item]');
      if (t.hasAttribute('data-up') && item.previousElementSibling) box.insertBefore(item, item.previousElementSibling);
      if (t.hasAttribute('data-down') && item.nextElementSibling) box.insertBefore(item.nextElementSibling, item);
      reindex(list);
    }
  });

  // предупреждение о несохранённых изменениях
  var form = document.getElementById('editForm');
  if (form) {
    var dirty = false;
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('change', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
      if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
  }
})();
