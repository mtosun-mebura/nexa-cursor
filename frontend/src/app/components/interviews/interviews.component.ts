import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableModule } from '@angular/material/table';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatChipsModule } from '@angular/material/chips';
import { HttpClient } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';

interface Interview {
  id: number;
  vacancy_id: number;
  user_id: number;
  company_id: number;
  scheduled_at: string;
  status: 'scheduled' | 'completed' | 'cancelled' | 'no_show';
  notes?: string;
  feedback?: string;
  created_at: string;
  updated_at: string;
  vacancy?: {
    title: string;
  };
  user?: {
    first_name: string;
    last_name: string;
    email: string;
  };
  company?: {
    name: string;
  };
}

@Component({
  selector: 'app-interviews',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatProgressSpinnerModule,
    MatSnackBarModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatChipsModule,
    FormsModule,
    ReactiveFormsModule
  ],
  template: `
    <div class="interviews-container">
      <div class="header-section">
        <h1>Interviews Beheer</h1>
        <button mat-raised-button color="primary" (click)="openCreateDialog()">
          <mat-icon>add</mat-icon>
          Nieuw Interview
        </button>
      </div>
      
      <div class="filters-section">
        <mat-card>
          <mat-card-content>
            <div class="filters-grid">
              <mat-form-field appearance="outline">
                <mat-label>Status</mat-label>
                <mat-select [(ngModel)]="statusFilter" (selectionChange)="applyFilters()">
                  <mat-option value="">Alle statussen</mat-option>
                  <mat-option value="scheduled">Gepland</mat-option>
                  <mat-option value="completed">Voltooid</mat-option>
                  <mat-option value="cancelled">Geannuleerd</mat-option>
                  <mat-option value="no_show">Niet verschenen</mat-option>
                </mat-select>
              </mat-form-field>
              
              <mat-form-field appearance="outline">
                <mat-label>Zoeken</mat-label>
                <input matInput [(ngModel)]="searchTerm" (input)="applyFilters()" placeholder="Zoek op naam, vacature...">
                <mat-icon matSuffix>search</mat-icon>
              </mat-form-field>
            </div>
          </mat-card-content>
        </mat-card>
      </div>
      
      <div class="content-section" *ngIf="!loading; else loadingTpl">
        <mat-card>
          <mat-card-content>
            <table mat-table [dataSource]="filteredInterviews" class="interviews-table">
              <ng-container matColumnDef="candidate">
                <th mat-header-cell *matHeaderCellDef>Kandidaat</th>
                <td mat-cell *matCellDef="let interview">
                  <div class="candidate-info">
                    <div class="candidate-name">{{ interview.user?.first_name }} {{ interview.user?.last_name }}</div>
                    <div class="candidate-email">{{ interview.user?.email }}</div>
                  </div>
                </td>
              </ng-container>
              
              <ng-container matColumnDef="vacancy">
                <th mat-header-cell *matHeaderCellDef>Vacature</th>
                <td mat-cell *matCellDef="let interview">{{ interview.vacancy?.title }}</td>
              </ng-container>
              
              <ng-container matColumnDef="company">
                <th mat-header-cell *matHeaderCellDef>Bedrijf</th>
                <td mat-cell *matCellDef="let interview">{{ interview.company?.name }}</td>
              </ng-container>
              
              <ng-container matColumnDef="scheduled">
                <th mat-header-cell *matHeaderCellDef>Gepland</th>
                <td mat-cell *matCellDef="let interview">{{ interview.scheduled_at | date:'medium' }}</td>
              </ng-container>
              
              <ng-container matColumnDef="status">
                <th mat-header-cell *matHeaderCellDef>Status</th>
                <td mat-cell *matCellDef="let interview">
                  <mat-chip [color]="getStatusColor(interview.status)" selected>
                    {{ getStatusLabel(interview.status) }}
                  </mat-chip>
                </td>
              </ng-container>
              
              <ng-container matColumnDef="actions">
                <th mat-header-cell *matHeaderCellDef>Acties</th>
                <td mat-cell *matCellDef="let interview">
                  <button mat-icon-button (click)="viewInterview(interview)" matTooltip="Bekijken">
                    <mat-icon>visibility</mat-icon>
                  </button>
                  <button mat-icon-button (click)="editInterview(interview)" matTooltip="Bewerken">
                    <mat-icon>edit</mat-icon>
                  </button>
                  <button mat-icon-button (click)="deleteInterview(interview)" matTooltip="Verwijderen">
                    <mat-icon>delete</mat-icon>
                  </button>
                </td>
              </ng-container>
              
              <tr mat-header-row *matHeaderRowDef="displayedColumns"></tr>
              <tr mat-row *matRowDef="let row; columns: displayedColumns;"></tr>
            </table>
            
            <div class="no-data" *ngIf="filteredInterviews.length === 0">
              <mat-icon>event_busy</mat-icon>
              <p>Geen interviews gevonden</p>
            </div>
          </mat-card-content>
        </mat-card>
      </div>
      
      <ng-template #loadingTpl>
        <div class="loading-container">
          <mat-progress-spinner mode="indeterminate"></mat-progress-spinner>
          <p>Interviews laden...</p>
        </div>
      </ng-template>
    </div>
  `,
  styles: [`
    .interviews-container {
      padding: 20px;
    }
    
    .header-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .header-section h1 {
      margin: 0;
      color: #333;
    }
    
    .filters-section {
      margin-bottom: 20px;
    }
    
    .filters-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
    }
    
    .interviews-table {
      width: 100%;
    }
    
    .interviews-table th {
      font-weight: bold;
      color: #333;
    }
    
    .candidate-info {
      display: flex;
      flex-direction: column;
    }
    
    .candidate-name {
      font-weight: 500;
      color: #333;
    }
    
    .candidate-email {
      font-size: 12px;
      color: #666;
    }
    
    .no-data {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px;
      color: #666;
    }
    
    .no-data mat-icon {
      font-size: 48px;
      width: 48px;
      height: 48px;
      margin-bottom: 16px;
    }
    
    .loading-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px;
    }
    
    @media (max-width: 768px) {
      .header-section {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
      }
      
      .filters-grid {
        grid-template-columns: 1fr;
      }
    }
  `]
})
export class InterviewsComponent implements OnInit {
  loading = true;
  interviews: Interview[] = [];
  filteredInterviews: Interview[] = [];
  statusFilter = '';
  searchTerm = '';
  
  displayedColumns = ['candidate', 'vacancy', 'company', 'scheduled', 'status', 'actions'];

  constructor(
    private http: HttpClient,
    private snackBar: MatSnackBar,
    private dialog: MatDialog,
    private fb: FormBuilder
  ) {}

  ngOnInit() {
    this.loadInterviews();
  }

  loadInterviews() {
    this.http.get<Interview[]>('http://localhost:8000/api/admin/interviews').subscribe({
      next: (data) => {
        this.interviews = data;
        this.filteredInterviews = data;
        this.loading = false;
      },
      error: (error) => {
        console.error('Error loading interviews:', error);
        this.snackBar.open('Fout bij laden interviews', 'Sluiten', { duration: 3000 });
        this.loading = false;
      }
    });
  }

  applyFilters() {
    this.filteredInterviews = this.interviews.filter(interview => {
      const matchesStatus = !this.statusFilter || interview.status === this.statusFilter;
      const matchesSearch = !this.searchTerm || 
        interview.user?.first_name?.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        interview.user?.last_name?.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        interview.vacancy?.title?.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        interview.company?.name?.toLowerCase().includes(this.searchTerm.toLowerCase());
      
      return matchesStatus && matchesSearch;
    });
  }

  getStatusColor(status: string): string {
    switch (status) {
      case 'scheduled': return 'primary';
      case 'completed': return 'accent';
      case 'cancelled': return 'warn';
      case 'no_show': return 'warn';
      default: return 'primary';
    }
  }

  getStatusLabel(status: string): string {
    switch (status) {
      case 'scheduled': return 'Gepland';
      case 'completed': return 'Voltooid';
      case 'cancelled': return 'Geannuleerd';
      case 'no_show': return 'Niet verschenen';
      default: return status;
    }
  }

  openCreateDialog() {
    // TODO: Implement create dialog
    this.snackBar.open('Functie wordt binnenkort toegevoegd', 'Sluiten', { duration: 3000 });
  }

  viewInterview(interview: Interview) {
    // TODO: Implement view dialog
    this.snackBar.open('Functie wordt binnenkort toegevoegd', 'Sluiten', { duration: 3000 });
  }

  editInterview(interview: Interview) {
    // TODO: Implement edit dialog
    this.snackBar.open('Functie wordt binnenkort toegevoegd', 'Sluiten', { duration: 3000 });
  }

  deleteInterview(interview: Interview) {
    if (confirm('Weet je zeker dat je dit interview wilt verwijderen?')) {
      this.http.delete(`http://localhost:8000/api/admin/interviews/${interview.id}`).subscribe({
        next: () => {
          this.snackBar.open('Interview verwijderd', 'Sluiten', { duration: 3000 });
          this.loadInterviews();
        },
        error: (error) => {
          console.error('Error deleting interview:', error);
          this.snackBar.open('Fout bij verwijderen interview', 'Sluiten', { duration: 3000 });
        }
      });
    }
  }
}
