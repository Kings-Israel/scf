<template>
  <div>
    <div class="p-3 d-flex justify-content-between">
      <div class="d-flex flex-wrap">
        <div class="">
          <input type="text" class="form-control form-search" id="defaultFormControlInput" placeholder="PR ID" aria-describedby="defaultFormControlHelp" />
        </div>
        <div class="mx-2">
          <input type="text" class="form-control form-search" id="defaultFormControlInput" placeholder="Debit From" aria-describedby="defaultFormControlHelp" />
        </div>
        <div class="mx-2">
          <input type="text" class="form-control form-search" id="defaultFormControlInput" placeholder="Company Name" aria-describedby="defaultFormControlHelp" />
        </div>
        <div class="mx-2">
          <input class="form-control form-search" type="date" value="2021-06-18" id="html5-date-input" />
        </div>
        <div class="mx-2">
          <select class="form-select form-search" id="exampleFormControlSelect">
            <option value="">Status</option>
            <option value="1">Pending</option>
            <option value="2">Approved</option>
            <option value="3">Denied</option>
          </select>
        </div>
        <div class="mt-1">
          <select class="form-select form-search" id="exampleFormControlSelect">
            <option value="">Sort By</option>
            <option value="1">Asc</option>
            <option value="2">Desc</option>
          </select>
        </div>
      </div>
      <div class="d-flex">
        <div style="height: fit-content">
          <select class="form-select" id="exampleFormControlSelect1">
            <option value="1">10</option>
            <option value="2">20</option>
            <option value="3">50</option>
          </select>
        </div>
        <button type="button" class="btn btn-primary h-50" style="height: fit-content"><i class='ti ti-download ti-sm'></i></button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr class="">
            <th>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
              </div>
            </th>
            <th>CBS Transaction ID</th>
            <th>Pay Date</th>
            <th>Payment Date</th>
            <th>Transaction Type</th>
            <th>Vendor</th>
            <th>Debit From</th>
            <th>Credit To</th>
            <th>Amount</th>
            <th>Transaction Ref</th>
            <th>Invoice No</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <tr class="text-nowrap">
            <td>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
              </div>
            </td>
            <td><span class="fw-medium">003478</span></td>
            <td>24 Nov 2023</td>
            <td>24 Nov 2023</td>
            <td>Loan Disbursement</td>
            <td class="text-primary text-decoration-underline">1Account</td>
            <td class="text-primary text-decoration-underline">GameAnchor</td>
            <td class="">345767</td>
            <td class="text-success">Ksh 51000</td>
            <td>36543quy32</td>
            <td class="text-primary text-decoration-underline">INV_2233</td>
          </tr>
          <tr class="text-nowrap">
            <td>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
              </div>
            </td>
            <td><span class="fw-medium">003478</span></td>
            <td>24 Nov 2023</td>
            <td>24 Nov 2023</td>
            <td>Loan Disbursement</td>
            <td class="text-primary text-decoration-underline">1Account</td>
            <td class="text-primary text-decoration-underline">GameAnchor</td>
            <td class="">345767</td>
            <td class="text-success">Ksh 51000</td>
            <td>36543quy32</td>
            <td class="text-primary text-decoration-underline">INV_2233</td>
          </tr>
          <tr class="text-nowrap">
            <td>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
              </div>
            </td>
            <td><span class="fw-medium">003478</span></td>
            <td>24 Nov 2023</td>
            <td>24 Nov 2023</td>
            <td>Loan Disbursement</td>
            <td class="text-primary text-decoration-underline">1Account</td>
            <td class="text-primary text-decoration-underline">GameAnchor</td>
            <td class="">345767</td>
            <td class="text-success">Ksh 51000</td>
            <td>36543quy32</td>
            <td class="text-primary text-decoration-underline">INV_2233</td>
          </tr>
          <tr class="text-nowrap">
            <td>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
              </div>
            </td>
            <td><span class="fw-medium">003478</span></td>
            <td>24 Nov 2023</td>
            <td>24 Nov 2023</td>
            <td>Loan Disbursement</td>
            <td class="text-primary text-decoration-underline">1Account</td>
            <td class="text-primary text-decoration-underline">GameAnchor</td>
            <td class="">345767</td>
            <td class="text-success">Ksh 51000</td>
            <td>36543quy32</td>
            <td class="text-primary text-decoration-underline">INV_2233</td>
          </tr>
        </tbody>
      </table>
    </div>
    <nav aria-label="Page navigation" class="mt-2">
      <ul class="pagination justify-content-end">
        <li class="page-item prev">
          <a class="page-link" href="javascript:void(0);"><i class="ti ti-chevrons-left ti-xs"></i></a>
        </li>
        <li class="page-item">
          <a class="page-link" href="javascript:void(0);">1</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="javascript:void(0);">2</a>
        </li>
        <li class="page-item active">
          <a class="page-link" href="javascript:void(0);">3</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="javascript:void(0);">4</a>
        </li>
        <li class="page-item">
          <a class="page-link" href="javascript:void(0);">5</a>
        </li>
        <li class="page-item next">
          <a class="page-link" href="javascript:void(0);"><i class="ti ti-chevrons-right ti-xs"></i></a>
        </li>
      </ul>
    </nav>
  </div>
</template>
<script>
export default {
  name: 'PaymentReports'
}
</script>
