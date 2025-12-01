(function () {
    const tableBody = document.querySelector('#package-table tbody');
    const inboundForm = document.querySelector('#inbound-form');
    const inboundResult = document.querySelector('#inbound-result');
    const statusFilter = document.querySelector('#status-filter');
    const skuFilter = document.querySelector('#filter-sku');
    const batchFilter = document.querySelector('#filter-batch');
    const refreshBtn = document.querySelector('#refresh-list');
    const inventorySummary = document.querySelector('#inventory-summary');

    let currentPackages = Array.isArray(window.MRS_INITIAL_PACKAGES) ? window.MRS_INITIAL_PACKAGES : [];
    let currentInventory = window.MRS_INITIAL_INVENTORY || { summary: [], details: {} };

    function renderPackages(packages) {
        tableBody.innerHTML = '';

        if (!packages || packages.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="muted">暂无数据</td></tr>';
            return;
        }

        const rows = packages.map(pkg => {
            const actions = [];
            if (pkg.status === 'in_stock') {
                actions.push(`<button class="btn btn-small" data-action="ship" data-id="${pkg.package_id}">确认出库</button>`);
                actions.push(`<button class="btn btn-small btn-secondary" data-action="void" data-id="${pkg.package_id}">标记损耗</button>`);
            } else {
                actions.push('<span class="muted">—</span>');
            }

            return `
                <tr>
                    <td>${pkg.sku_name}</td>
                    <td>${pkg.batch_code}</td>
                    <td>${pkg.box_number}</td>
                    <td>${pkg.spec_info || ''}</td>
                    <td>${pkg.status}</td>
                    <td>${pkg.inbound_time || ''}</td>
                    <td>${actions.join(' ')}</td>
                </tr>
            `;
        });

        tableBody.innerHTML = rows.join('');
    }

    function renderInventory(inv) {
        inventorySummary.innerHTML = '';
        const summary = inv.summary || [];
        const details = inv.details || {};

        if (summary.length === 0) {
            inventorySummary.innerHTML = '<p class="muted">暂无在库数据</p>';
            return;
        }

        summary.forEach(item => {
            const sku = item.sku_name;
            const detailList = details[sku] || [];
            const detailHtml = detailList.map(row => `<li>${row.batch_code} - ${row.box_number} (${row.spec_info || '无规格'}) | 入库: ${row.inbound_time}</li>`).join('');
            const block = document.createElement('div');
            block.className = 'inventory-card';
            block.innerHTML = `
                <h3>${sku} <span class="badge">${item.total} 箱</span></h3>
                <ul>${detailHtml}</ul>
            `;
            inventorySummary.appendChild(block);
        });
    }

    function handleInboundSubmit(event) {
        event.preventDefault();
        const formData = new FormData(inboundForm);

        fetch('/mrs/ap/index.php?action=save_inbound_api', {
            method: 'POST',
            body: formData,
        })
            .then(res => res.json())
            .then(data => {
                inboundResult.style.display = 'block';
                inboundResult.className = data.success ? 'alert alert-success' : 'alert alert-error';
                inboundResult.textContent = data.message || (data.success ? '操作成功' : '操作失败');

                if (data.success) {
                    inboundForm.reset();
                    loadPackages();
                    loadStats();
                }
            })
            .catch(() => {
                inboundResult.style.display = 'block';
                inboundResult.className = 'alert alert-error';
                inboundResult.textContent = '请求失败，请稍后重试';
            });
    }

    function loadPackages() {
        const params = new URLSearchParams({
            status: statusFilter.value,
            sku_name: skuFilter.value,
            batch_code: batchFilter.value,
        });

        fetch('/mrs/ap/index.php?action=list_packages_api&' + params.toString())
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentPackages = data.data || [];
                    renderPackages(currentPackages);
                }
            })
            .catch(() => {
                tableBody.innerHTML = '<tr><td colspan="7" class="muted">加载失败</td></tr>';
            });
    }

    function loadStats() {
        fetch('/mrs/ap/index.php?action=stats_api')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentInventory = data.data.inventory;
                    renderInventory(currentInventory);
                }
            })
            .catch(() => {
                inventorySummary.innerHTML = '<p class="muted">库存快照加载失败</p>';
            });
    }

    function updateStatus(packageId, status) {
        const formData = new FormData();
        formData.append('package_id', packageId);
        formData.append('status', status);

        fetch('/mrs/ap/index.php?action=update_status_api', {
            method: 'POST',
            body: formData,
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadPackages();
                    loadStats();
                } else {
                    alert(data.message || '更新失败');
                }
            })
            .catch(() => alert('网络异常，稍后再试'));
    }

    tableBody.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const action = target.dataset.action;
        const id = target.dataset.id;
        if (!action || !id) return;

        if (action === 'ship') {
            updateStatus(id, 'shipped');
        } else if (action === 'void') {
            updateStatus(id, 'void');
        }
    });

    inboundForm.addEventListener('submit', handleInboundSubmit);
    refreshBtn.addEventListener('click', (event) => {
        event.preventDefault();
        loadPackages();
    });

    renderPackages(currentPackages);
    renderInventory(currentInventory);
})();
